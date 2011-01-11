<?php /*********************************************************************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class patchwork_tokenizer_namespaceRemover extends patchwork_tokenizer
{
	protected

	$callbacks  = array(
		'tagNs'     => T_NAMESPACE,
		'tagNsSep'  => T_NS_SEPARATOR,
		'tagNsUse'  => array(T_USE_CLASS, T_USE_FUNCTION, T_USE_CONSTANT, T_TYPE_HINT),
		'tagNsName' => array(T_NAME_CLASS, T_NAME_FUNCTION),
	),
	$dependencies = array('constFuncResolver', 'namespaceResolver', 'classInfo');


	protected function tagNs(&$token)
	{
		if (in_array(T_NAME_NS, $token[2]))
		{
			$this->register('tagNsEnd');
			$token[1] = ' ';
		}
	}

	protected function tagNsEnd(&$token)
	{
		switch ($token[0])
		{
		case '{':
		case ';':
		case $this->prevType:
			$this->namespace = strtr($this->namespace, '\\', '_');
			$this->unregister(__FUNCTION__);
			if (';' !== $token[0]) return;
		}

		$token[1] = '';
	}

	protected function tagNsSep(&$token)
	{
		if (T_STRING === $this->prevType) $token[1] = strtr($token[1], '\\', '_');
		else if (T_NS_SEPARATOR !== $this->prevType) $token[1] = '';
	}

	protected function tagNsUse(&$token)
	{
		$token[1] = strtr($token[1], '\\', '_');
		$this->nsResolved = strtr($this->nsResolved, '\\', '_');
		'_' === substr($this->nsResolved, 0, 1) && $this->nsResolved[0] = '\\';
	}

	protected function tagNsName(&$token)
	{
		if ($this->namespace && T_CLASS !== $this->scope->type && T_INTERFACE !== $this->scope->type)
		{
			if (in_array(T_NAME_CLASS, $token[2]))
			{
				$this->class->nsName = strtr($this->class->nsName, '\\', '_');
				$this->class->name   = $this->class->nsName;
			}

			$this->code[count($this->code) - 1] .= $this->namespace;
		}
	}
}

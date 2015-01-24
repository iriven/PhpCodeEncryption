<?php
/**
* IrivenPhpCodeEncryption - PHP class to obfuscate your code source.
* Copyright (C) 2015 Iriven France Software, Inc. 
*
* Licensed under The GPL V3 License
* Redistributions of files must retain the above copyright notice.
*
* @Copyright 		Copyright (C) 2015 Iriven France Software, Inc.
* @package 		IrivenPhpCodeEncryption
* @Since 		Version 1.0.0
* @link 		https://github.com/iriven/IrivenPhpCodeEncryption The IrivenPhpCodeEncryption GitHub project
* @author 		Alfred Tchondjo (original founder) <iriven@yahoo.fr>
* @license  		GPL V3 License(http://www.gnu.org/copyleft/gpl.html)
*
* ==================  NOTICE  =======================
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 3
* of the License, or any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
* or contact the author by mail at: <contact@iriven.com>.
**/

class IrivenPhpCodeEncryption {

    public $compressCode=true;		// Strip comments and whitespace
    public $removeComments=true;	// Strip comments (Automatically enabled when using $strip).
    public $base64Encode=true;		// Base64 passover
    private $globalScopes=array();	// Global variables
    private $listedClasses=array();	// Class specific variables
    private $listedFunctions=array();	// Function variables
    private $listedVariables=array();		// To keep up with various variables.
    private $phpCode=null;
    private $tokens=null;
    private $class=false;
    private $function=false;
	private $sourceFile = null;
    private $depth=0;		// Keep track of how deep in curly brackets we are, so we can unset $class and $function when needed.
    private $installedAlgorithms = array();
    private $reserved=array('$_GET','$_POST','$_REQUIRE','$_SERVER','$_ENV','$_SESSION','$_FILES');
    public function __construct()
    {
        defined('T_ML_COMMENT') or define('T_ML_COMMENT',T_COMMENT);
        $this->installedAlgorithms = hash_algos();
        return $this;
    }
    /**
     * @param $source
     * @param bool $isFile
     * @return $this|bool|IrivenPhpCodeEncryption
     */
    public function loadCode($source,$isFile = true)
    {
        if(!$source) return false;
        switch($isFile):
            case false:
                $this->phpCode = $source;
                break;
            default:
                if(!is_readable($source)) return false;
                $this->phpCode = file_get_contents($source);
				$this->sourceFile = array('dirname'=>pathinfo($source,PATHINFO_DIRNAME),'filename'=>pathinfo($source,PATHINFO_FILENAME));
                break;
        endswitch;
        return $this->tokenize();
    }

    /**
     * @return bool|null
     */
    public function getCode()
    {
        if (!$this->phpCode) return false;
        return $this->phpCode;
    }

    /**
     * @param $destination
     * @return bool
     */
    public function save($destination=null) {
        if (!$this->phpCode) return false;
        if(!$destination)
        {
            $destination = ($this->sourceFile)? implode(DIRECTORY_SEPARATOR,$this->sourceFile) : 'IrivenPhpCode';
            if($destination === 'IrivenPhpCode')
            {
                $date = new DateTime('now',new DateTimeZone('Europe/Paris'));
                $destination .= $date->format('YmdHis');
            }
            $destination .= '_Encrypted.php';
        }
        if(($destination === basename($destination)) and isset($this->sourceFile['dirname']))
            $destination= rtrim($this->sourceFile['dirname'],DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$destination;
        if(file_put_contents($destination,$this->phpCode,LOCK_EX)) return true;
        return false;
    }
    /**
     * @return string
     */
    private function createHash() {
        $number=round((mt_rand(1,mt_rand(1000,10000))*mt_rand(1,10))/mt_rand(1,10));
        $activeAlgorithm=$this->installedAlgorithms[mt_rand(0,(count($this->installedAlgorithms)-1))];
        $hash=hash($activeAlgorithm,$number);
        return $hash;
    }

    /**
     * @param $data
     */
    private function encode($data) {
        if ($this->compressCode) $data=preg_replace('/[\n\t\s]+/',' ',$data);
        $data=preg_replace('/^\<\?(php)*/','',$data);
        $data=preg_replace('/\?\>$/','',$data);
        $data=str_replace(array('\"','$','"'),array('\\\"','\$','\"'),$data);
        $data=trim($data);
        if ($this->base64Encode) {
            $data=base64_encode("$data");
            $data="<?php \$code=base64_decode(\"$data\"); eval(\"return eval(\\\"\$code\\\");\") ?>\n";
        } else $data="<?php eval(eval(\"$data\")); ?>\n";
        $this->phpCode=$data;
    }

    /**
     * @param $data
     * @return string
     */
    private function stringEncode($data) {
        for ($i=0;$i<=strlen($data)-1;$i++) {
            $chr=ord(substr($data,$i,1));
            if ($chr==32||$chr==34||$chr==39) $tmpdata[]=chr($chr); // Space, leave it alone.
            elseif ($chr==92&&preg_match('/\\\(n|t|r|s)/',substr($data,$i,2))) {
                // New line, leave it alone, and add the next char with it.
                $tmpdata[]=substr($data,$i,2);
                $i++; // Skip the next character.
            }
            else $tmpdata[]='\x'.strtoupper(base_convert($chr,10,16));
        }
        if (!empty($tmpdata)) $data=implode('',$tmpdata);
        return $data;
    }

    /**
     * @param $var
     * @param null $function
     * @param null $class
     * @return null|string
     */
    private function createVar($var,$function=null,$class=null) {
        while (empty($string))
        {
            $string="\$_{$this->createHash()}";
            switch($function):
                case (!$function):
                    switch($class)
                    {
                        case(!$class):
                            if (!empty($this->globalScopes[$var]))  return $this->globalScopes[$var];
                            else
                            {
                                if (in_array($string,$this->globalScopes)) $string=null;
                                else $this->globalScopes[$var]=$string;
                            }
                            break;
                        default:
                            if (!empty($this->listedClasses[$class]['globals'][$var]))
                                return $this->listedClasses[$class]['globals'][$var];
                            else
                            {
                                if (!empty($this->listedClasses[$class]['globals'])&&in_array($string,$this->listedClasses[$class]['globals'])) $string=null;
                                else $this->listedClasses[$class]['globals'][$var]=$string;
                            }
                            break;
                    }
                    break;
                default:
                    switch($class)
                    {
                        case(!$class):
                            if (!empty($this->listedFunctions[$var])) return $this->listedFunctions[$var];
                            else {
                                if (in_array($string,$this->listedFunctions)) $string=null;
                                else $this->listedFunctions[$var]=$string;
                            }
                            break;
                        default:
                            if (!empty($this->listedClasses[$class]['functions'][$function][$var])) return $this->listedClasses[$class]['functions'][$function][$var];
                            else {
                                if (!empty($this->listedClasses[$class]['functions'][$function])&&in_array($string,$this->listedClasses[$class]['functions'][$function])) $string=null;
                                else $this->listedClasses[$class]['functions'][$function][$var]=$string;
                            }
                            break;
                    }
                    break;
            endswitch;
        }
        return $string;
    }
    /**
     * @return $this|bool
     */
    public function compileDatas() {
        if (!$this->tokens) return false;
        foreach ($this->tokens as $sKey=>&$tokenValue)
        {
            try
            {
                if (is_array($tokenValue)) {
                    switch ($tokenValue[0]) {
                        case T_FUNCTION:
                            if ($this->tokens[$sKey - 2][0] == T_VARIABLE) $this->function = $this->tokens[$sKey - 2][1];
                            elseif ($this->tokens[$sKey + 2][0] == T_STRING) $this->function = $this->tokens[$sKey + 2][1];
                            break;
                        case T_CLASS:
                        case T_INTERFACE: //new
                            $this->class = $this->tokens[$sKey + 2][1];
                            break;
                        case T_VARIABLE:
                            if ($tokenValue[1] == '$this') break; // Absolutely skip $this.
                            if (in_array($tokenValue[1], $this->reserved)) {
                                // Skip renaming anything that should be ignored, but encode it so that it's not in plaintext.
                                $tokenValue[1] = "\${$this->stringEncode(substr($tokenValue[1], 1))}";
                                break;
                            }
                            if (!empty($this->tokens[$sKey - 1][1]) && $this->tokens[$sKey - 1][0] == T_DOUBLE_COLON) break; // Static class variable. Don't touch it.
                            if (!empty($this->tokens[$sKey - 2][1]) && $this->tokens[$sKey - 2][0] == T_GLOBAL)
                            {
                                if ($this->function)
                                {
                                    if ($this->class) $tokenValue[1] = $this->listedVariables['classes'][$this->class][$this->function][$tokenValue[1]] = $this->createVar($tokenValue[1]);
                                    else $tokenValue[1] = $this->listedVariables['functions'][$this->function][$tokenValue[1]] = $this->createVar($tokenValue[1]);
                                }
                                elseif ($this->class) throw new Exception('PHP syntax error found. Exiting.');
                            }
                            elseif ($this->function)
                            {
                                if ($this->class)
                                {
                                    if (!empty($this->listedVariables['classes'][$this->class][$this->function][$tokenValue[1]])) $tokenValue[1] = $this->listedVariables['classes'][$this->class][$this->function][$tokenValue[1]];
                                    else $tokenValue[1] = $this->createVar($tokenValue[1], $this->function, $this->class);
                                }
                                else
                                {
                                    if (!empty($this->listedVariables['functions'][$this->function][$tokenValue[1]])) $tokenValue[1] = $this->listedVariables['functions'][$this->function][$tokenValue[1]];
                                    else $tokenValue[1] = $this->createVar($tokenValue[1], $this->function);
                                }
                            }
                            elseif ($this->class)
                            {
                                $tokenValue[1] = $this->createVar($tokenValue[1], null, $this->class);
                            }
                            else
                            {
                                $tokenValue[1] = $this->createVar($tokenValue[1]);
                            }
                            break;
                        case T_OBJECT_OPERATOR:
                            if ($this->tokens[$sKey - 1][1] == '$this' && $this->function && $this->class)
                            {
                                $this->tokens[$sKey - 1][1] = '$' . $this->stringEncode('this');
                                if ($this->tokens[$sKey + 2] == '(') ; // Function, encode $this and leave it alone.
                                else $this->tokens[$sKey + 1][1] = substr($this->createVar("\${$this->tokens[$sKey + 1][1]}", null, $this->class), 1);
                            } else throw new Exception('PHP syntax error found: $this referenced outside of a class.');
                            break;
                        case T_DOUBLE_COLON:
                            if ($this->tokens[$sKey - 1][1] == '$this') {
                                if ($this->function && $this->class) {
                                    $this->tokens[$sKey - 1][1] = '$' . $this->stringEncode('this');
                                    if ($this->tokens[$sKey + 2] == '(') ; // Function, leave it alone.
                                    else $this->tokens[$sKey + 1][1] = $this->createVar($this->tokens[$sKey + 1][1], null, $this->class);
                                } else throw new Exception('PHP syntax error found: $this referenced outside of a class.');
                            } else {
                                if ($this->tokens[$sKey + 2] == '(') ; // Function, leave it alone.
                                else $this->tokens[$sKey + 1][1] = $this->createVar($this->tokens[$sKey + 1][1], null, $this->tokens[$sKey - 1][1]);
                            }
                            break;
                        case T_COMMENT:
                        case T_DOC_COMMENT:
                        case T_ML_COMMENT: // Will be equal to T_COMMENT if not in PHP 4.
                            if ($this->removeComments || $this->compressCode) $tokenValue[1] = '';
                            break;
                        case T_START_HEREDOC:
                            // Automatically turn whitespace stripping off, because formatting needs to stay the same.
                            $this->compressCode = false;
                            break;
                        case T_END_HEREDOC:
                            $tokenValue[1] = "\n{$tokenValue[1]}";
                            break;
                        case T_CURLY_OPEN:
                        case T_DOLLAR_OPEN_CURLY_BRACES:
                        case T_STRING_VARNAME:
                            if ($this->function) $this->depth++;
                            break;
                    }
                } else {
                    switch ($tokenValue) {
                        case '{':
                            if ($this->function) $this->depth++;
                            break;
                        case '}':
                            $this->depth--;
                            if ($this->depth < 0) $this->depth = 0;
                            if ($this->function && $this->depth == 0) {
                                $this->listedFunctions = array(); // Empty function variables array
                                $this->listedVariables['functions'] = array(); // Empty any temp variables
                                $this->function = false;
                            } elseif ($this->class && $this->depth == 0) {
                                $this->listedVariables['classes'] = array(); // Empty any temp variables
                                $this->class = false;
                            }
                            break;
                    }
                }
            }
            catch(Exception $e)
            {
                die($e->getMessage());
            }
        }
        $this->detokenize();
        return $this;
    }
    /**
     * @return $this|bool
     */
    private function tokenize() {
        if (!$this->phpCode) return false;
        $this->tokens=token_get_all($this->phpCode);
        return $this;
    }

    /**
     *
     */
    private function detokenize() {
        if (!$this->tokens) return; // No tokens to parse. Exit.
        $data = array();
        foreach ($this->tokens as &$tokenValue) {
            if (is_array($tokenValue)) {
                switch ($tokenValue[0]) {
                    // Looks like overkill, but helpful when extending to encode certain things differently.
                    case T_INCLUDE:
                    case T_INCLUDE_ONCE:
                    case T_REQUIRE:
                    case T_REQUIRE_ONCE:
                    case T_STATIC:
                    case T_PUBLIC:
                    case T_PRIVATE:
                    case T_PROTECTED:
                    case T_FUNCTION:
                    case T_CLASS:
                    case T_INTERFACE: //new
                    case T_EXTENDS:
                    case T_INSTANCEOF: //new
                    case T_IMPLEMENTS: //new
                    case T_GLOBAL:
                    case T_NEW:
                    case T_ECHO:
                    case T_DO:
                    case T_WHILE:
                    case T_SWITCH:
                    case T_CASE:
                    case T_BREAK:
                    case T_CONTINUE:
                    case T_ENDSWITCH:
                    case T_CONST:
                    case T_DECLARE:
                    case T_ENDDECLARE:
                    case T_FOR:
                    case T_ENDFOR:
                    case T_FOREACH:
                    case T_ENDFOREACH:
                    case T_IF:
                    case T_ENDIF:
                    case T_RETURN:
                    case T_UNSET:
                    case T_EXIT:
                    case T_VAR:
                    case T_STRING:
                    case T_ENCAPSED_AND_WHITESPACE:
                    case T_CONSTANT_ENCAPSED_STRING:
                        $tokenValue[1]=$this->stringEncode($tokenValue[1]);
                        break;
                }
                $data[]=$tokenValue[1];
            }
            else $data[]=$tokenValue;
        }
        $data=implode('',$data);
        $this->encode($data);
    }

}
<?php

/**
 * This scripts generates the restructuredText for the class API.
 *
 * Change the MANAPHP_DIR constant to point to the root directory in the ManaPHP source code
 *
 * php gen-api.php
 */

define('MANAPHP_DIR', 'd:/wamp/www/manaphp/ManaPHP');
$languages = ['en', 'zh'];


if (!file_exists(MANAPHP_DIR)) {
    /** @noinspection ThrowRawExceptionInspection */
    throw new \Exception('MANAPHP directory does not exist');
}

require MANAPHP_DIR . '/Autoloader.php';
\ManaPHP\Autoloader::register(false);


class ApiRstGeneratorException extends \Exception
{

}

class ClassRstGeneratorException extends \Exception
{

}

class ClassRstGenerator
{
    /*
     * @var string
     */
    private $_className;

    /**
     * @var ReflectionClass
     */
    private $_classReflector;


    /**
     * @param string $className
     */
    public function __construct($className)
    {
        $this->_className = $className;
        $this->_classReflector = new ReflectionClass($className);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if ($this->_classReflector->isInterface()) {
            $prefix = 'Interface';
        }elseif ($this->_classReflector->isFinal()) {
            $prefix = 'Final class';
        } elseif ($this->_classReflector->isAbstract()) {
             $prefix = 'Abstract class';
        } else {
             $prefix = 'Class';
        }

        $rstClassName = str_replace("\\", "\\\\", $this->_className);
        $rstTitle = "$prefix **$rstClassName**" . PHP_EOL;
        $rstTitle .= str_repeat('=', strlen($rstTitle) - strlen(PHP_EOL)) . PHP_EOL . PHP_EOL;

        return $rstTitle;
    }

    /**
     * @return string
     */
    public function getExtends()
    {
        $parentClassReflector = $this->_classReflector->getParentClass();
        if ($parentClassReflector !== false) {
            $extendsClassName = $parentClassReflector->getName();
            $rstExtendsClassName = str_replace('\\', '\\\\', $extendsClassName);
            $extendsClassLink = str_replace('\\', '_', $extendsClassName);
            if (strpos($extendsClassName, 'ManaPHP') === 0) {
                if ($parentClassReflector->isAbstract()) {
                    $prefix = 'abstract class';
                } else {
                    $prefix = 'class';
                }

                $rstExtends = "*extends* $prefix :doc:`$rstExtendsClassName <$extendsClassLink>`";
            } else {
                $rstExtends = "*extends* $extendsClassName";
            }

            $rstExtends .=PHP_EOL.PHP_EOL;
        } else {
            $rstExtends = '';
        }

        return $rstExtends;
    }

    /**
     * @return string
     */
    public function getImplements()
    {
        if (count($this->_classReflector->getInterfaceNames()) !== 0) {
            $implements = [];
            foreach ($this->_classReflector->getInterfaceNames() as $interfaceName) {
                if (strpos($interfaceName, 'ManaPHP') === 0) {
                    $rstInterfaceName = str_replace('\\', '\\\\', $interfaceName);
                    $InterfaceLink = str_replace('\\', '_', $interfaceName);
                    $implements[$interfaceName] = ":doc:`$rstInterfaceName <$InterfaceLink>`";
                } else {
                    $implements[$interfaceName] = $interfaceName;
                }
            }
            sort($implements);

            $rstImplements = '*implements* ' . implode(', ', $implements) . PHP_EOL . PHP_EOL;
        } else {
            $rstImplements = '';
        }

        return $rstImplements;
    }

    /**
     * @param string $phpDocCode
     * @return array
     */
    protected function _extractCode($phpDocCode)
    {
        if ($phpDocCode === '') {
            return [];
        }

        $lines = [];
        foreach (preg_split('#\n#', $phpDocCode) as $line) {
            $line = ltrim($line, '*');
            $trimmedLine = trim($line);
            if ($trimmedLine === '/\**') {
                $line = str_replace('/\**', '/**', $line);
            } elseif ($trimmedLine === '*\/') {
                $line = str_replace('*\/', '*/', $line);
            }

            $lines[] = $line;
        }

        unset($lines[0]);
        array_pop($lines);

        return $lines;
    }

    /**
     * @param string $phpDocDescription
     * @return array
     */
    protected function _extractDescription($phpDocDescription)
    {
        $lines = [];
        $description = '';
        foreach (explode("\n", $phpDocDescription) as $line) {

            $line = ltrim($line, '*');
            if ($line === '') {
                if ($description !== '') {
                    $lines[] = $description;
                    $description = '';
                }
            } else {
                if ($description === '') {
                    $description = ltrim($line);
                } else {
                    $description .= $line;
                }
            }
        }

        if ($description !== '') {
            $lines[] = $description;
        }

        foreach ($lines as $key => $line) {
            if ($line === '' || preg_match('#^[\w\\\\]+$#', $line)) {
                unset($lines[$key]);
            } else {
                break;
            }
        }

        return $lines;
    }

    /**
     * @param string $phpDoc
     * @param boolean $removeAtTags
     * @return string
     */
    public function formatPhpDoc($phpDoc, $removeAtTags)
    {
        $lines = [];

        foreach (preg_split('#[\r\n]+#', $phpDoc) as $line) {
            $line = trim($line);

            if ($removeAtTags && strpos($line, '* @') === 0) {
                continue;
            }

            $lines[] = $line;
        }

        unset($lines[0]);
        array_pop($lines);

        return implode("\n", $lines);
    }

    /**
     * @param string $phpDoc
     * @param string $target
     * @param boolean $removeAtTags
     * @return array
     * @throws ClassRstGeneratorException
     */
    public function getDescription($phpDoc, $removeAtTags = false, $target = null)
    {
        $formattedPhpDoc = $this->formatPhpDoc($phpDoc, $removeAtTags);

        $phpDocDescription=preg_replace('#\*<code>.*</code>#sm','',$formattedPhpDoc);

        if ($phpDocDescription !== '') {
            $descriptions = $this->_extractDescription($phpDocDescription);
            $rstDescription = implode(PHP_EOL, $descriptions);
        } else {
            $rstDescription = '';
        }

        return $rstDescription;
    }

    /**
     * @param string $phpDoc
     * @param string $target
     * @return array
     * @throws ClassRstGeneratorException
     */
    public function getCode($phpDoc,$target = null)
    {
        $formattedPhpDoc = $this->formatPhpDoc($phpDoc, false);

        if (strpos($formattedPhpDoc, '*<code>') !== false) {
            if (!preg_match('#\*<code>.*</code>#sm', $formattedPhpDoc, $match)) {
                throw new ClassRstGeneratorException('<code> segment has error: ' . $target);
            }

            $phpDocCode = $match[0];
        } else {
            $phpDocCode = '';
        }

        $rstCode = '';
        if ($phpDocCode !== '') {
            $codes = $this->_extractCode($phpDocCode);
            if (count($codes) !== 0) {
                $rstCode = PHP_EOL . PHP_EOL . '.. code-block:: php' . PHP_EOL . PHP_EOL;
                $rstCode .= '    <?php' . PHP_EOL . PHP_EOL;

                foreach ($codes as $code) {
                    $rstCode .= '    ' . $code . PHP_EOL;
                }
            }
        }

        return $rstCode;
    }


    /**
     * @param \ReflectionMethod $methodReflector
     * @return string
     */
    public function getMethodDeclaration($methodReflector)
    {
        echo $methodReflector->getDeclaringClass()->getName().':'.$methodReflector->getName().PHP_EOL;
        $methodDocComment = $methodReflector->getDocComment();

        $rstModifier = implode(' ', Reflection::getModifierNames($methodReflector->getModifiers()));

        if (preg_match('#@return(.+)#', $methodDocComment, $match)) {
            $return = $match[1];
            $return = trim($return);
            $docReturn = $return;
        } else {
            $docReturn = '';
        }

        $rstMethodName = '**' . $methodReflector->getName() . '**';

        $rstParameters = [];
        foreach ($methodReflector->getParameters() as $parameterReflector) {
            $parameterName = $parameterReflector->getName();
            if (preg_match('#@param(.*)\$' . $parameterName . '#', $methodDocComment, $match)) {
                $parameterType = trim($match[1]);
            } else {
                $parameterType = 'unknown';
            }

            $rstParameter="*$parameterType* \$$parameterName";
            if($parameterReflector->isOptional() &&strpos($methodReflector->getDeclaringClass()->getName(),'ManaPHP') ===0){
                $parameterDefaultValue=$parameterReflector->getDefaultValue();
                if(is_int($parameterDefaultValue)){
                    $parameterDefaultValue=$parameterReflector->getDefaultValueConstantName();
                    if($parameterDefaultValue ===null){
                        $parameterDefaultValue=$parameterReflector->getDefaultValue();
                    }
                }elseif($parameterDefaultValue ===null){
                    $parameterDefaultValue='null';
                }elseif(is_bool($parameterDefaultValue)){
                    if($parameterDefaultValue ===true){
                        $parameterDefaultValue='true';
                    }else{
                        $parameterDefaultValue='false';
                    }
                }else{
                    $parameterDefaultValue=var_export($parameterDefaultValue,true);
                }
                $rstParameter .=' ='.$parameterDefaultValue;
            }

            $rstParameters[] = $rstParameter;

        }

        return $rstModifier . ' ' . $docReturn . ' ' . $rstMethodName . ' (' . implode(', ', $rstParameters) . ')';
    }

    /**
     * @return string
     * @throws ClassRstGeneratorException
     */
    public function getMethods()
    {
        $methodReflectors = $this->_classReflector->getMethods();
        if (count($methodReflectors) === 0) {
            return '';
        }

        $rstMethods = [];
        foreach ($methodReflectors as $methodReflector) {
            $rstMethod = $this->getMethodDeclaration($methodReflector);
            $methodPhpDoc = $methodReflector->getDocComment();
            if ($methodPhpDoc !== false) {
                $target=$methodReflector->getDeclaringClass()->getName().':'.$methodReflector->getName();
                $rstMethodDescription=$this->getDescription($methodPhpDoc,true,$target);
                $rstMethodCode=$this->getCode($methodPhpDoc,$target);

                $rstMethod .= PHP_EOL . PHP_EOL . $rstMethodDescription . PHP_EOL . PHP_EOL;
                $rstMethod .= $rstMethodCode;
            }

            $rstMethods[] = $rstMethod;
        }

        $rst = 'Methods' . PHP_EOL;
        $rst .= str_repeat('-', strlen($rst) - strlen(PHP_EOL)) . PHP_EOL . PHP_EOL;

        foreach ($rstMethods as $rstMethod) {
            $rst .= $rstMethod . PHP_EOL . PHP_EOL;
        }

        return $rst;
    }

    /**
     * @return string
     */
    public function getSourceCodeLink()
    {
        $classPath = str_replace('\\', '/', $this->_className);
        $githubLink = "https://github.com/manaphp/manaphp/blob/master/$classPath.php";
        $rstRole = '.. role:: raw-html(raw)' . PHP_EOL . '   :format: html' . PHP_EOL . PHP_EOL;
        $rstSourceCodeLink = ':raw-html:`<a href="' . $githubLink . '" class="btn btn-default btn-sm">Source on GitHub</a>`' . PHP_EOL . PHP_EOL;

        return $rstRole . $rstSourceCodeLink;
    }

    /**
     * @return string
     * @throws ClassRstGeneratorException
     */
    public function getClassRst()
    {
        $rst = '';

        $rstTitle = $this->getTitle();
        $rst .= $rstTitle;

        $rstExtends = $this->getExtends();
        $rst .= $rstExtends;

        $rstImplements = $this->getImplements();
        $rst .= $rstImplements;

        $rstSourceCodeLink = $this->getSourceCodeLink();
        $rst .= $rstSourceCodeLink;

        $classPhpDoc = $this->_classReflector->getDocComment();

        if ($classPhpDoc !== false) {
            $rstClassDescription=$this->getDescription($classPhpDoc,true);
            $rstClassCode=$this->getCode($classPhpDoc);

            $rst .= $rstClassDescription;
            $rst .= $rstClassCode;
        }

        $rstMethods = $this->getMethods();
        $rst .= $rstMethods;
        return $rst;
    }
}

/**
 * Class ApiGenerator
 */
class ApiRstGenerator
{
    /**
     * @param string $directory
     * @return array
     */
    public function findSourceFiles($directory)
    {
        $sourceFiles = [];

        $recursiveDirectoryIterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);

        /** @var $iterator RecursiveDirectoryIterator[] */
        $iterator = new RecursiveIteratorIterator($recursiveDirectoryIterator);
        foreach ($iterator as $item) {
            if ($item->getExtension() === 'php' && $item->getFilename()[0] !== '.') {
                $sourceFiles[] = $item->getRealPath();
            }
        }

        return $sourceFiles;
    }

    /**
     * @param array $sourceFiles
     * @throws ApiRstGeneratorException
     */
    public function loadSourceFiles($sourceFiles)
    {
        foreach ($sourceFiles as $file) {
            if (!in_array($file, get_included_files(), true)) {
                /** @noinspection PhpIncludeInspection */
                require $file;
            }
        }

        $includedFiles = get_included_files();
        unset($includedFiles[0]);

        $notIncludedFiles = array_diff($sourceFiles, $includedFiles);
        if (count($notIncludedFiles) !== 0) {
            throw new ApiRstGeneratorException('some source files loaded failed: [' . implode(',' . PHP_EOL, $notIncludedFiles) . ']');
        }
    }

    /**
     * @return array
     * @throws ApiRstGeneratorException
     */
    public function getFrameworkClasses()
    {
        $classes = [];

        foreach (get_declared_classes() as $className) {
            if (strpos($className, 'ManaPHP') === 0) {
                $classes[] = $className;
            }
        }

        foreach (get_declared_interfaces() as $className) {
            if (strpos($className, 'ManaPHP') === 0) {
                $classes[] = $className;
            }
        }

        sort($classes);

        return $classes;
    }

    /**
     * @param array $classes
     * @return array
     */
    public function getViolatePsrClasses($classes)
    {
        $includedFiles = implode(',', get_included_files());
        $notFollowPsrClasses = [];
        foreach ($classes as $className) {
            if (strpos($includedFiles, $className . '.php') === false) {
                $notFollowPsrClasses[] = $className;
            }
        }

        return $notFollowPsrClasses;
    }

    /**
     * @param string $frameworkDirectory
     * @param array $languages
     * @throws \ClassRstGeneratorException|\ApiRstGeneratorException
     */
    public function generateClassesRst($frameworkDirectory, $languages)
    {
        $sourceFiles = $this->findSourceFiles($frameworkDirectory);
        $this->loadSourceFiles($sourceFiles);
        $frameworkClasses = $this->getFrameworkClasses();
        $notFollowPsrClasses = $this->getViolatePsrClasses($frameworkClasses);
        if (count($notFollowPsrClasses) !== 0) {
            throw new ApiRstGeneratorException('some classes not violate PSR: [' . PHP_EOL . implode(',' . PHP_EOL, $notFollowPsrClasses) . ']');
        }

        foreach ($languages as $language) {
            foreach ($frameworkClasses as $className) {
                $classDocGenerator = new ClassRstGenerator($className);
                $rst = $classDocGenerator->getClassRst();

                $file = __DIR__ . "/$language/" . str_replace('\\', '_', $className) . '.rst';
                $file = str_replace('ManaPHP', 'Phalcon', $file);
                $rst = str_replace('ManaPHP', 'Phalcon', $rst);
                file_put_contents($file, $rst);
            }
        }
    }
}


$apiGenerator = new ApiRstGenerator(MANAPHP_DIR);

//$sourceFiles=$apiGenerator->findSourceFiles(MANAPHP_DIR);
//$apiGenerator->loadSourceFiles($sourceFiles);
//$frameworkClasses=$apiGenerator->getFrameworkClasses();
//$notFollowPsrClasses=$apiGenerator->getViolatePsrClasses($frameworkClasses);
//if(count($notFollowPsrClasses) !==0){
//    throw new ApiGeneratorException('some classes not violate PSR: ['.PHP_EOL.implode(','.PHP_EOL,$notFollowPsrClasses).']');
//}
//
//foreach($frameworkClasses as $className){
//    $classDocGenerator=new ClassDocGenerator($className);
//    $classDocGenerator->getClassSummary((new ReflectionClass($className))->getDocComment());
//}

$apiGenerator->generateClassesRst(MANAPHP_DIR, ['en']);
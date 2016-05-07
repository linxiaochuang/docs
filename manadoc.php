<?php

require __DIR__.'/Parsedown.php';


class Application{

    protected $_config=[];
    protected $_cwd='';
    protected $_publicDirectory;
    protected $_parseDown;
    public function __construct()
    {
        $this->_cwd=getcwd();
        $configFile=$this->_cwd .'/config.ini';
        if(!file_exists($configFile)){
            exit('Config file is not exists: '.$configFile);
        }
        $this->_config=parse_ini_file($configFile);
        $this->_parseDown=new Parsedown();
    }

    public function command(){

    }

    public function generateCommand(){
        $publicDirectory=$this->_cwd.'/public';
        if(is_dir($publicDirectory) &&!$this->_rmDirectory($publicDirectory)){
            exit('Clear public directory failed: '.$publicDirectory);
        }
        if(!mkdir($publicDirectory,0755) &&!is_dir($publicDirectory)){
            exit('Create public directory failed: '.$publicDirectory);
        }

        $themeDirectory=$this->_cwd.'/themes/'.$this->_config['theme'];
        if(!is_dir($themeDirectory)){
            exit('Theme directory is not exists: '.$themeDirectory);
        }

        $chapterTemplate=$themeDirectory.'/templates/chapter.phtml';
        if(!is_file($chapterTemplate)){
            exit('Chapter template is not exists: '.$chapterTemplate);
        }

        $sourcesDirectory=$this->_cwd.'/sources';

        if(is_dir($sourcesDirectory.'/images')){
            $this->_copyDirectory($sourcesDirectory.'/images',$publicDirectory);
        }

        $chapters=[];

        $mdFiles=glob($sourcesDirectory.'/*.md');
        foreach($mdFiles as $mdFile){
            $chapter=[];
            $chapter['url']='/'.pathinfo($mdFile,PATHINFO_FILENAME).'.html';

            $mdContent=file_get_contents($mdFile);
            if(preg_match('@#\s+(.*)\s*@',$mdContent,$match) !==1){
                exit('markdown file have no h1: '.$mdContent);
            }
            $title=$match[1];
            $chapter['title']=trim($title);
            $chapter['h2']=[];
            if(preg_match_all('@^##\s+(.*)\s*$@',$mdContent,$matches) !==0){
                foreach($matches as $match){
                    $h2=trim($match[0]);
                    $chapter['h2'][]=['title'=>$h2,'url'=>$chapter['url'].'#',str_replace([' '],'-',$h2)];
                }
            }

            $chapters[]=$chapter;
            $markdown=$this->_renderMarkdown($mdContent);
            ob_start();
            include $chapterTemplate;
            $content=ob_get_clean();
            $htmlFile=$publicDirectory.'/'.pathinfo($mdFile,PATHINFO_FILENAME).'.html';
            if(!file_put_contents($htmlFile, $content)){
                exit('Save html failed: '.$htmlFile);
            }
        }
    }

    protected function _copyDirectory($src,$dst){

    }

    protected function _renderMarkdown($content){
        return $this->_parseDown->text($content);
    }

    protected function _rmDirectory($directory){
        foreach(glob($directory.'/*') as $file){
            if(is_dir($file)){
                $this->_rmDirectory($file);
            }else{
                unlink($file);
            }
        }
        return rmdir($directory);
    }
}

$app=new Application();
$app->generateCommand();
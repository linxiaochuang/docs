<?php

require __DIR__ . '/Parsedown.php';

class Application
{
    protected $_config = [];
    protected $_theme_dir;
    protected $_sources_dir;
    protected $_output_dir;

    public function __construct()
    {
        $configFile = 'config.ini';
        if (!file_exists($configFile)) {
            exit('Config file is not exists: ' . $configFile);
        }
        $this->_config = parse_ini_file($configFile);

        if (isset($this->_config['theme_dir'])) {
            $this->_theme_dir = realpath($this->_config['theme_dir']);
            if ($this->_theme_dir === false) {
                exit('Theme directory is not exists: ' . $this->_config['theme_dir']);
            }
        } else {
            exit('theme_dir is not set in config.ini');
        }

        if (isset($this->_config['sources_dir'])) {
            $this->_sources_dir = realpath($this->_config['sources_dir']);
            if ($this->_sources_dir === false) {
                exit('Sources directory is not exists: ' . $this->_config['sources_dir']);
            }
        } else {
            exit('sources_dir is not set in config.ini');
        }

        if (isset($this->_config['output_dir'])) {
            $this->_output_dir = realpath($this->_config['output_dir']);
            if ($this->_output_dir === false) {
                exit('Output directory is not exists: ' . $this->_config['output_dir']);
            }
        } else {
            exit('output_dir is not set in config.ini');
        }
    }

    public function command()
    {

    }

    public function getChapters($sourcesDirectory)
    {
        $chapters = [];

        $mdFiles = glob($sourcesDirectory . '/*.md');
        foreach ($mdFiles as $mdFile) {
            $chapter = [];
            $chapter['url'] = '/' . pathinfo($mdFile, PATHINFO_FILENAME) . '.html';

            $mdContent = file_get_contents($mdFile);
            if (preg_match('@#\s+(.*)\s*@', $mdContent, $match) !== 1) {
                exit('markdown file have no h1: ' . $mdContent);
            }
            $title = $match[1];
            $chapter['title'] = trim($title);
            $chapter['h2'] = [];
            if (preg_match_all('@[^#]##\s+(.*)\s*@', $mdContent, $matches) !== 0) {
                foreach ($matches[1] as $match) {
                    $h2 = trim($match);
                    $chapter['h2'][] = ['title' => $h2, 'url' => $chapter['url'] . '#' . str_replace([' '], '-', $h2)];
                }
            }

            $chapters[$mdFile] = $chapter;
        }
        return $chapters;
    }

    public function generateCommand()
    {
        if (is_dir($this->_output_dir) && !$this->_rmDirectory($this->_output_dir)) {
            exit('Clear public directory failed: ' . $this->_output_dir);
        }
        if (!mkdir($this->_output_dir, 0755) && !is_dir($this->_output_dir)) {
            exit('Create public directory failed: ' . $this->_output_dir);
        }

        $chapterTemplate = $this->_theme_dir . '/templates/chapter.phtml';
        if (!is_file($chapterTemplate)) {
            exit('Chapter template is not exists: ' . $chapterTemplate);
        }
        $templateResources = $this->_theme_dir . '/resources';
        if (is_dir($templateResources)) {
            $this->_recurseCopy($templateResources, $this->_output_dir);
        }

        if (is_dir($this->_sources_dir . '/images')) {
            $this->_recurseCopy($this->_sources_dir . '/images', $this->_output_dir);
        }

        $chapters = $this->getChapters($this->_sources_dir);

        $indexTemplate = $this->_theme_dir . '/templates/index.phtml';
        $title = isset($this->_config['index_title']) ? $this->_config['index_title'] : 'index';

        $indexHtml = $this->render($indexTemplate, ['chapters' => $chapters, 'title' => $title,'root_path'=>$this->_config['root_path']]);
        $indexFile = $this->_output_dir . '/index.html';

        if (!file_put_contents($indexFile, $indexHtml)) {
            exit('Save index.html failed: ' . $indexFile);
        }

        $parseDown = new Parsedown();

        $mdFiles = glob($this->_sources_dir . '/*.md');
        foreach ($mdFiles as $mdFile) {
            $mdContent = file_get_contents($mdFile);
            $chapterHtml = $this->render($chapterTemplate, [
                'chapters' => $chapters,
                'content' => $parseDown->text($mdContent),
                'title' => strtr($this->_config['chapter_title'],['{title}'=>$chapters[$mdFile]['title']]),
                'root_path'=>$this->_config['root_path'],
                'current_chapter'=>$mdFile,
            ]);

            $htmlFile = $this->_output_dir . '/' . pathinfo($mdFile, PATHINFO_FILENAME) . '.html';
            if (!file_put_contents($htmlFile, $chapterHtml)) {
                exit('Save chapter html failed: ' . $htmlFile);
            }
        }
    }

    public function render($template, $vars)
    {
        extract($vars, EXTR_SKIP);

        ob_start();

        /** @noinspection PhpIncludeInspection */
        require $template;

        return ob_get_clean();
    }

    public function _recurseCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst,0755,true);
        while (false !== ($file = readdir($dir))) {
            if ($file[0] !== '.') {
                if (is_dir($src . '/' . $file)) {
                    $this->_recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    protected function _rmDirectory($directory)
    {
        $dir=opendir($directory);
        while($file=readdir($dir)) {
            if($file==='.' ||$file ==='..'){
                continue;
            }

            $file=$directory.'/'.$file;
            if (is_dir($file)) {
                $this->_rmDirectory($file);
            } else {
                unlink($file);
            }
        }
        closedir($dir);
        return rmdir($directory);
    }
}

$app = new Application();
$app->generateCommand();
<?php

namespace PlaylistGenerator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class Generator {

    /**
     * @var Filesystem
     */
    private $fs;

    private $config;

    private $baseDir;
    private $targetDir;

    private $mediaExtensions = [
        "*.mp3",
        "*.ogg",
        "*.flac"
    ];

    public function __construct($configFile) {
        $this->fs = new Filesystem();
        $this->config = Yaml::parseFile($configFile);
        $this->baseDir = $this->config['settings']['musicDir'];
        $this->targetDir = $this->config['settings']['targetDir'];
        if (substr($this->baseDir, -1) != '/') {
            $this->baseDir .= '/';
        }
        if (substr($this->targetDir, -1) != '/') {
            $this->targetDir .= '/';
        }
    }

    public function generate() {
        foreach ($this->config['playlists'] as $list) {
            switch ($list['type']) {
                case 'search':
                    $this->doFileSearch($list);
                    break;
                case 'm3uconvert':
                    $this->doM3uConvert($list);
                    break;
            }
        }
    }

    private function doFileSearch($list)
    {
        $finder = Finder::create()
            ->files()
            ->name($this->mediaExtensions);

        if (!empty($list['dirs'])) {
            foreach ($list['dirs'] as $dir) {
                $finder->in($this->baseDir.$dir);
            }
        };
        if (!empty($list['pathPatterns'])) {
            $finder->path($list['pathPatterns']);
        };
        if (!empty($list['notPathPatterns'])) {
            $finder->notPath($list['notPathPatterns']);
        };

        $newList = [];

        foreach ($finder as $file) {
            $path = $this->fs->makePathRelative($file->getRealPath(), $this->baseDir);
            if (substr($path, -1) == '/') {
                $path = substr($path, 0, -1);
            }
            $newList[] = $path;
        }

        $targetFile = $this->targetDir.$list['name'].'.m3u';
        if (!empty($list['copyFrom'])) {
            // copy base file
            $base = $this->targetDir.$list['copyFrom'].'.m3u';
            if ($this->fs->exists($base)) {
                $this->fs->copy($base, $targetFile);
            }

            $this->fs->appendToFile($targetFile, implode("\n", $newList));
        } else {
            $this->fs->dumpFile($targetFile, implode("\n", $newList));
        }

        echo "wrote ".count($newList)." to ".$targetFile.PHP_EOL;
    }

    /**
     * checks for m3u list in the directory, transforms paths and removes headers
     *
     * @param array $list spec
     */
    private function doM3uConvert($list)
    {
        $finder = Finder::create()
            ->files()
            ->in($this->baseDir.$list['dir'])
            ->name('*.m3u');

        foreach ($finder as $file) {
            $newList = [];
            $newListFilename = $file->getFilename();

            $content = str_replace("\r", "", $file->getContents());
            $lines = array_map('trim', explode("\n", $content));

            foreach ($lines as $line) {
                if (empty($line) || preg_match('/\#(.*)/', $line)) {
                    continue;
                }

                if (substr($line, 0, strlen($this->baseDir)) == $this->baseDir) {
                    $line = substr($line, strlen($this->baseDir));
                }

                $newList[] = $line;
            }

            $targetFile = $this->targetDir.$newListFilename;
            $this->fs->dumpFile($targetFile, implode("\n", $newList));

            echo "wrote ".count($newList)." to ".$targetFile.PHP_EOL;
        }
    }

}

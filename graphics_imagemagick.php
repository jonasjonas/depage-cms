<?php

class graphics_imagemagick extends graphics {
    protected $command;
    protected $executable;

    public function __construct($options) {
        parent::__construct($options);

        $this->executable = $options['executable'];
    }

    protected function crop($width, $height, $x = 0, $y = 0) {
        // '+' for positive offset (the '-' is already there)
        $x = ($x < 0) ? $x : '+' . $x;
        $y = ($y < 0) ? $y : '+' . $y;

        $this->command .= " -gravity NorthWest -crop {$width}x{$height}{$x}{$y}! -flatten";
        $this->size = array($width, $height);
    }

    protected function resize($width, $height) {
        $newSize = $this->dimensions($width, $height);

        $this->command .= " -resize {$newSize[0]}x{$newSize[1]}!";
        $this->size = $newSize;
    }

    protected function thumb($width, $height) {
        $this->command .= " -gravity Center -thumbnail {$width}x{$height} -extent {$width}x{$height}";
        $this->size = array($width, $height);
    }

    protected function getImageSize() {
        if (is_callable('getimagesize')) {
            return getimagesize($this->input);
        } else {
            $identify = preg_replace('/convert$/', 'identify', $this->executable);
            // TODO escape
            exec("{$identify} -format \"%wx%h\" {$this->input}" . ' 2>&1', $commandOutput, $returnStatus);
            if ($returnStatus === 0) {
                return explode('x', $commandOutput[0]);
            } else {
                throw new graphics_exception(implode("\n", $commandOutput));
            }
        }
    }

    public function render($input, $output = null) {
        parent::render($input, $output);

        if ($this->bypassTest()) {
            $this->bypass();
        } else {
            $this->processQueue();

            $background = $this->getBackground();
            $quality    = $this->getQuality();

            $this->command = "{$this->executable} {$background} ( {$this->input}{$this->command}";

            $this->command .= " ) -flatten -quality {$quality} {$this->output}";

            $this->execCommand();
        }
    }

    protected function execCommand() {
        $command = str_replace('!', '\!', escapeshellcmd($this->command));

        exec($command . ' 2>&1', $commandOutput, $returnStatus);
        if ($returnStatus != 0) {
            throw new graphics_exception(implode("\n", $commandOutput));
        }
    }

    protected function getBackground() {
        $background = "-size {$this->size[0]}x{$this->size[1]}";

        if ($this->background[0] === '#') {
            $background .= " -background {$this->background}";
        } else if ($this->background == 'checkerboard') {
            $background .= " -background none pattern:checkerboard";
        } else {
            if ($this->outputFormat == 'jpg') {
                $background .= " -background #FFF";
            } else {
                $background .= " -background none";
            }
        }

        return $background;
    }
}

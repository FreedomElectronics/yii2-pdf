<?php

namespace junqi\pdf;

use Yii;
use yii\base\Component;

/**
 * A wrapper class for wkhtmltopdf
 *
 * @see https://github.com/wkhtmltopdf/wkhtmltopdf
 * @see `wkhtmltopdf -H`
 */
class Pdf extends Component
{
    /** @var string $tmpDir */
    public $tmpDir = '@runtime/tmp-pdf/';

    /** @var array $options */
    public $options = [];

    /** @var string $params */
    protected $params = '';

    /** @var string $command */
    protected $command = '';

    /** @var string $inputSource */
    protected $inputSource = '';

    /** @var string $tmpInputFile */
    protected $tmpInputFile = '';

    /** @var string $outputSource */
    protected $outputSource = '';

    /** @var string $error */
    protected $error = '';

    /** @var int $errorCode */
    protected $errorCode = 0;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->command = 'wkhtmltopdf';
        $this->initOptions();
        $this->buildParams();

        $this->tmpDir = rtrim(Yii::getAlias($this->tmpDir), '/') . '/';
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }

        register_shutdown_function(function () {
            if ($this->tmpInputFile !== '') {
                unlink($this->tmpInputFile);
            }

            unlink($this->outputSource);
        });
    }

    /**
     * @param string $html
     * @return $this
     */
    public function loadHtml($html)
    {
        $this->tmpInputFile = tempnam($this->tmpDir, '');
        $newName = $this->tmpDir . basename($this->tmpInputFile) . '.html';
        rename($this->tmpInputFile, $newName);
        $this->tmpInputFile = $newName;

        $handle = fopen($this->tmpInputFile, 'w');
        fwrite($handle, $html);
        fclose($handle);

        $this->inputSource = $this->tmpInputFile;

        return $this;
    }

    /**
     * @param string $resource HTML page
     * @return $this
     */
    public function loadResource($resource)
    {
        $this->inputSource = $resource;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $this->parseOptions($options));
        $this->buildParams();

        return $this;
    }

    /**
     * @return void
     */
    protected function initOptions()
    {
        $this->options = $this->parseOptions($this->options);
    }

    /**
     * @param array $options
     * @return array
     * @throws PdfException
     */
    protected function parseOptions(array $options)
    {
        $result = [];
        foreach ($options as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $result[] = '--' . $this->uncamelize($key) . ' ' . $value;
            } elseif (is_array($value)) {
                $result[] = '--' . $this->uncamelize($key) . ' ' . implode(' ', $value);
            } elseif ($value === true) {
                $result[] = '--' . $this->uncamelize($key);
            } else {
                throw new PdfException('Unsupported type for pdf options.');
            }
        }

        return $result;
    }

    /**
     * @param string $str
     * @param string $delimiter
     * @return string
     */
    protected function uncamelize($str, $delimiter = '-')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $delimiter . "$2", $str));
    }

    /**
     * @return void
     */
    protected function buildParams()
    {
        $this->params = implode(' ', $this->options);
    }

    /**
     * @return void
     */
    protected function createCommand()
    {
    	if ($this->params !== '') {
    	    $this->command .= ' ' . $this->params;
        }

    	$this->command .= ' ' . $this->inputSource;
        $this->outputSource = tempnam($this->tmpDir, '');
    	$this->command .= ' ' . $this->outputSource;
    }

    /**
     * @return $this
     * @throws PdfException
     */
    public function execute()
    {
        $this->createCommand();

        $process = proc_open($this->command, [2 => ['pipe', 'w']], $pipes);

    	if (is_resource($process)) {
            $this->error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $this->errorCode = proc_close($process);

//            if ($this->errorCode !== 0) {
//                throw new PdfException($this->error);
//            }

            return $this;
        }

        throw new PdfException('Process could not be open.');
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function getFile($fileName = '')
    {
        if ($fileName !== '') {
            $newName = $this->tmpDir . $fileName;
            rename($this->outputSource, $newName);
            $this->outputSource = $newName;
        }

        return $this->outputSource;
    }

    /**
     * @param string $fileName
     * @return void
     */
    public function sendFile($fileName = '')
    {
        Yii::$app->response->sendFile($this->outputSource, $fileName);
    }
}

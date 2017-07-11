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

    /** @var string $tmpFile */
    protected $tmpFile = '';

    /** @var string $params */
    protected $params = '';

    /** @var string $command */
    protected $command = '';

    /** @var string $inputSource */
    protected $inputSource = '';

    /** @var string $inputHtml */
    protected $inputHtml = '';

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

        register_shutdown_function(function () {
            unlink($this->tmpFile);
        });
    }

    /**
     * @param string $html
     * @return $this
     */
    public function loadHtml($html)
    {
        $this->inputSource = '-';
        $this->inputHtml = $html;

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
     * @params array $options
     * @return array
     */
    protected function parseOptions(array $options)
    {
        $result = [];
        foreach ($options as $key => $value) {
            if (is_string($value)) {
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

    	$this->tmpDir = Yii::getAlias($this->tmpDir);
    	if (!is_dir($this->tmpDir)) {
    	    @mkdir($this->tmpDir, 0755, true);
        }

        $this->tmpFile = tempnam($this->tmpDir, 'pdf-');
    	$this->outputSource = $this->tmpFile;
    	$this->command .= ' ' . $this->outputSource;
    }

    /**
     * @return $this
     * @throws PdfException
     */
    public function execute()
    {
        $this->createCommand();

    	$process = proc_open($this->command, [
    			['pipe', 'r'],
    			['pipe', 'w'],
    			['pipe', 'w']
    		], $pipes);

    	if (is_resource($process)) {
    	    if ($this->inputSource === '-') {
                fwrite($pipes[0], $this->inputHtml);
                fclose($pipes[0]);
            }

//            $output = stream_get_contents($pipes[1]);
//            fclose($pipes[1]);

            $this->error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $this->errorCode = proc_close($process);

            if ($this->errorCode !== 0) {
                $this->error = substr($this->error, 0, strpos($this->error, "\n\n"));

                throw new PdfException($this->error);
            }

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
            rename($this->tmpFile, $this->tmpDir . $fileName);
            $this->outputSource = $this->tmpDir . $fileName;
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

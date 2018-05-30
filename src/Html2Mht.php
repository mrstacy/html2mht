<?php
namespace MrStacy\Html2Mht;

use MrStacy\Html2Mht\Exception\FileNotFoundException;
use MrStacy\Html2Mht\Exception\MhtFileCreationException;

class Html2Mht
{
    const FILE_EXT_MID = 'mid';
    const FILE_EXT_HTM = 'htm';
    const FILE_EXT_HTML = 'html';
    const FILE_EXT_TXT = 'txt';
    const FILE_EXT_CGI = 'cgi';
    const FILE_EXT_CSS = 'php';
    const FILE_EXT_PHP = 'css';
    const FILE_EXT_JPEG = 'jpg';
    const FILE_EXT_JPG = 'jpeg';
    const FILE_EXT_GIF = 'gif';
    const FILE_EXT_PNG = 'png';
    
    const MIME_TYPE_MIDI = 'audio/sp-midi';
    const MIME_TYPE_HTML = 'text/html';
    const MIME_TYPE_JPEG = 'image/jpeg';
    const MIME_TYPE_PNG = 'image/png';
    const MIME_TYPE_GIF = 'image/gif';
    const MIME_TYPE_CSS = 'text/css';
    const MIME_TYPE_TEXT = 'text/plain';
    const MIME_TYPE_DEFAULT = 'application/octet-stream';
    
    const EXT_MIMES_TYPES = [
        self::FILE_EXT_MID => self::MIME_TYPE_MIDI,
        self::FILE_EXT_HTM => self::MIME_TYPE_HTML,
        self::FILE_EXT_HTML => self::MIME_TYPE_HTML,
        self::FILE_EXT_TXT => self::MIME_TYPE_TEXT,
        self::FILE_EXT_CGI => self::MIME_TYPE_TEXT,
        self::FILE_EXT_CSS => self::MIME_TYPE_CSS,
        self::FILE_EXT_PHP => self::MIME_TYPE_TEXT,
        self::FILE_EXT_JPEG => self::MIME_TYPE_JPEG,
        self::FILE_EXT_JPG => self::MIME_TYPE_JPEG,
        self::FILE_EXT_GIF => self::MIME_TYPE_GIF,
        self::FILE_EXT_PNG => self::MIME_TYPE_PNG,
    ];
    
    const INDEX_FILES = [
        'index.htm',
        'index.html'
    ];
    
    /**
     * @var string
     */
    private $inputFileName;
    
    /**
     * @var string
     */
    private $inputContent;
    
    /**
     * @var string
     */
    private $inputDir;

    /**
     * @var string
     */
    private $boundary;
    
    /**
     * @param string $inputHtmlFile
     * @throws FileNotFoundException
     */
    public function __construct(string $inputHtmlFile)
    {
        $this->inputFileName = $this->getInputIndexFile($inputHtmlFile);
        $this->inputContent = file_get_contents( $this->inputFileName );
        $this->boundary = $this->generateBoundary();
        $this->inputDir = dirname($this->inputFileName);
    }
    
    /**
     * Save generated MHT file content to MHT file
     * 
     * @param string $outputMhtFile
     * @return bool
     */
    public function generateMhtFile(string $outputMhtFile) : bool
    {
        $content = $this->generateContent();
        
        try {
            file_put_contents($outputMhtFile, $content);
        } catch ( \Exception $e ) {
            throw new MhtFileCreationException($e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Return generated MHT file content
     * 
     * @return string
     */
    public function generateContent() : string
    {
        $files = $this->getFilesToEncode();
        
        $returnValue = $this->getHeaders();
        $returnValue .= $this->encodeFile($this->inputFileName);

        foreach ( $files as $file )
        {
            $returnValue .= $this->encodeFile( $file );
        }
        
        $returnValue .= "--" . $this->boundary . "--\r\n";
        
        return $returnValue;
    }
    
    /**
     * Get the file path for input html index file
     * 
     * @param string $inputHtmlFile
     * @throws FileNotFoundException
     * @return string
     */
    private function getInputIndexFile(string $inputHtmlFile) : string
    {
        if ( file_exists($inputHtmlFile) && is_file($inputHtmlFile) ) {
            return $inputHtmlFile;
        }
        
        foreach ( self::INDEX_FILES as $indexFile )
        {
            $filePath = "$inputHtmlFile/$indexFile";
            if ( file_exists($filePath) ) {
                return $filePath;
            }
        }

        throw new FileNotFoundException("{$inputHtmlFile} does not exist");
    }
    
    /**
     * Get MHT file headers
     * 
     * @return string
     */
    private function getHeaders() : string
    {
        return join("\r\n", [
            "From: <html2mht>",
            "Date: " .  date('D, d M Y H:i:s O'),
            "MIME-Version: 1.0",
            "Content-Type: multipart/related;",
            "\tboundary=\"{$this->boundary}\"",
            "X-MimeOLE: Produced By html2mht",
            "",
            "This is a multi-part message in MIME format.",
            "",
        ]);
    }
    
    /**
     * Get the files that need to be encoded in the mhtfile
     * 
     * @return \Generator
     */
    private function getFilesToEncode() : \Generator
    {
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->inputDir)
        );
        
        foreach ($rii as $file) {
            if ($file->isDir()){
                continue;
            }
            
            $fileName = $this->getRelativePath( $file->getPathname());
            if ( strpos($this->inputContent,$fileName) > 0 ) {
                yield $file->getPathname();
            }
        }
    }
    
    /**
     * Get the relative path for file name 
     * 
     * @param string $fileName
     * @return string
     */
    private function getRelativePath(string $fileName) : string
    {
        $fileName = str_replace($this->inputDir, '', $fileName);
        $fileName = str_replace('\\','/', $fileName);
        $fileName = ltrim($fileName, '/');
        return $fileName;
    }
    
    /**
     * Get encoded file
     * 
     * @param string $filePath
     * @return string
     */
    private function encodeFile(string $filePath) : string 
    {
        $mimeType    = $this->getMimeType( $filePath );
        $fileContent = file_get_contents( $filePath );
        $displayFile = $this->getRelativePath( $filePath );
        
        if ( $mimeType == self::MIME_TYPE_HTML || $mimeType == self::MIME_TYPE_TEXT || $mimeType == self::MIME_TYPE_CSS  )
        {
            $returnValue = "--" . $this->boundary . "\r\n";
            $returnValue .= "Content-Type: $mimeType\r\n";
            $returnValue .= "Content-Transfer-Encoding: quoted-printable\r\n";
            $returnValue .= "Content-Location: $displayFile\r\n";
            $returnValue .= "\r\n";
            $returnValue .= quoted_printable_encode($fileContent);
            $returnValue .= "\r\n\r\n";
        }
        else
        {
            $returnValue = "--" . $this->boundary . "\r\n";
            $returnValue .= "Content-Type: $mimeType\r\n";
            $returnValue .= "Content-Transfer-Encoding: base64\r\n";
            $returnValue .= "Content-Location: $displayFile\r\n";
            $returnValue .= "\r\n";
            $returnValue .= chunk_split(base64_encode($fileContent), 76);
            $returnValue .= "\r\n";
        }
        
        return $returnValue;
        
    }
    
    /**
     * Generate a mht imbedded file boundary
     * 
     * @return string
     */
    private function generateBoundary() : string
    {
        return "----=_NextPart_" . strtoupper(md5(mt_rand()));
    }
    
    /**
     * Get mime type for a specific file
     * 
     * @param string $filePath
     * @return string
     */
    private function getMimeType(string $filePath) : string
    {
        $pathinfo = pathinfo($filePath);
        $extension = $pathinfo['extension'];
        
        if ( isset(self::EXT_MIMES_TYPES[$extension]) ) {
            return self::EXT_MIMES_TYPES[$extension];
        }
        
        return self::MIME_TYPE_DEFAULT;
    }
}
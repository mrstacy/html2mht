<?php
namespace MrStacy\Html2Mht\Tests;

use PHPUnit\Framework\TestCase;
use MrStacy\Html2Mht\Html2Mht;
use MrStacy\Html2Mht\Exception\FileNotFoundException;

class Html2MhtTest extends TestCase
{
    public function testGenerateFullPath()
    {
        $html2mht = new Html2Mht(__DIR__ . '/example/index.html');
        $output = $html2mht->generateContent();
        
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/facebook.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/linkedin.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/twitter.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: css/main.css'));
        self::assertGreaterThan(0,strpos($output, 'Links'));
    }
    
    public function testGenerateDir()
    {
        $html2mht = new Html2Mht(__DIR__ . '/example');
        $output = $html2mht->generateContent();
        
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/facebook.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/linkedin.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/twitter.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: css/main.css'));
        self::assertGreaterThan(0,strpos($output, 'Links'));
    }
    
    public function testFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        
        $html2mht = new Html2Mht(__DIR__ . '/notfound');
        $output = $html2mht->generateContent();
    }
    
    public function testGenerateFile()
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'Html2MhtTest.mht');

        $html2mht = new Html2Mht(__DIR__ . '/example');
        $html2mht->generateMhtFile($outputFile);
        
        $output = file_get_contents($outputFile);
        
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/facebook.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/linkedin.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: images/twitter.png'));
        self::assertGreaterThan(0,strpos($output, 'Content-Location: css/main.css'));
        self::assertGreaterThan(0,strpos($output, 'Links'));
        
        unlink($outputFile);
    }
}
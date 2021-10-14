<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class GeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $generator = new \Vestin\FilePreview\Generator();
        $generator->generate(__DIR__.'/testvideo.mp4',__DIR__.'/testjpg.jpg',[
            'scale' => '500:500/a'
        ]);

        $generator->generate(__DIR__.'/testjpg.jpg',__DIR__.'/testjpg2.jpg',[
            'width'=>"500",
            'height'=>"500",
        ]);

        $generator->generate(__DIR__.'/testfile.docx',__DIR__.'/testjpg3.jpg',[
            'width'=>"500",
            'height'=>"500",
        ]);

        $generator->generate(__DIR__.'/testfile.pdf',__DIR__.'/testjpg4.jpg',[
            'width'=>"500",
            'height'=>'500'
        ]);

        $this->assertTrue(true);
    }
}

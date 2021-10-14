<?php


namespace Vestin\FilePreview;


class Generator
{

    /**
     * @var array
     */
    public $allowOutputExt = [
        'gif','jpg','png'
    ];

    /**
     * @param string $input
     * @param string $output
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function generate(string $input,string $output,$options = []){

        if(strlen($input)==0 || is_file($input)==false){
            throw new \Exception('input file not exists!');
        }

        $inputInfo = pathinfo($input);
        $outputInfo = pathinfo($output);

        if(!isset($inputInfo['extension']) || !isset($outputInfo['extension'])){
            throw new \Exception('input/output file extension must exists!');
        }

        $inputExt = $inputInfo['extension'];
        $outputExt = $outputInfo['extension'];

        if(!in_array($outputExt,$this->allowOutputExt)){
            throw new \Exception('output file extension must be gif,jpg,png!');
        }

        $mimedb = json_decode(file_get_contents(__DIR__.'/db.json'),true);
        $inputFileType = 'other';

        foreach($mimedb as $index => $mime){
            if(isset($mime['extensions'])){
                if(in_array($inputExt,$mime['extensions'])){
                    $tmpExt = explode('/',$index)[0];
                    if( $tmpExt == 'image'){
                        $inputFileType = 'image';
                    }else if ($tmpExt == 'video'){
                        $inputFileType = 'video';
                    }else{
                        $inputFileType = 'other';
                    }

                    break;
                }
            }
        }

        if ( $inputFileType == 'video' ) {
            try {
                $ffmpegArgs = ['ffmpeg','-v quiet','-y', '-i', $input, '-vf', 'thumbnail', '-frames:v', '1', $output];
                if ($options['scale'] > 0) {
                    $ffmpegArgs[6]= 'thumbnail,scale=' . $options['scale'];
                }
                $this->exec($ffmpegArgs);
                return true;
            } catch (\Exception $e) {
                throw new \Exception('ffmpeg转换失败');
            }
        }

        if ( $inputFileType == 'image' ) {
            try {
                $convertArgs = ['convert'];
                $this->parseImageOptions($convertArgs,$options);
                $convertArgs[] = $input.'[0]';
                $convertArgs[] = $output;
                $this->exec($convertArgs);
                return true;
            } catch (\Exception $e) {
                throw new \Exception('图片转换失败');
            }
        }

        if ( $inputFileType == 'other' ) {

            try {
                // other => pdf
                $tempPDF = '/tmp/'. uniqid(true) . '.pdf';
                $this->exec(['unoconv','-e', 'PageRange=1', '-o', $tempPDF, $input]);

                // pdf => jpg
                $convertArgs = ['convert'];
                $tempJPG = '/tmp/'. uniqid(true) . '.jpg';
                $this->parseOtherOptions($convertArgs,$options);
                $convertArgs[] = $tempPDF.'[0]';
                $convertArgs[] = $tempJPG;
                $this->exec($convertArgs);

                // resize image
                $convertArgs = ['convert'];
                $this->parseImageOptions($convertArgs,$options);
                $convertArgs[] = $tempJPG;
                $convertArgs[] = $output;
                $this->exec($convertArgs);

                unlink($tempPDF);
                unlink($tempJPG);
                return true;
            } catch (\Exception $e) {
                throw new \Exception('转换失败',0, $e);
            }
        }

        return true;
    }

    /**
     * @param $convertArgs
     * @param $options
     */
    private function parseOtherOptions(&$convertArgs, $options){
        if(isset($options['quality'])){
            $convertArgs[] = '-quality';
            $convertArgs[] = $options['quality'];
        }

        if(isset($options['density'])){
            $convertArgs[] = '-quality';
            $convertArgs[] = $options['density'];
        }
    }

    /**
     * @param $convertArgs
     * @param $options
     */
    private function parseImageOptions(&$convertArgs, $options){
        if(isset($options['autorotate'])){
            $convertArgs[] = '-auto-orient';
        }

        if ($options['width'] > 0 && $options['height'] > 0) {
            $convertArgs[] = '-resize';
            $convertArgs[] = $options['width'] . 'x' . $options['height'];
        }
    }

    /**
     * @param array $commandArgs
     * @throws \Exception
     */
    public function exec(array $commandArgs){
        exec(implode(' ',$commandArgs),$output,$returnVar);
        if($returnVar==1){
            throw new \Exception('执行失败'. implode(' ',$commandArgs));
        }
    }
}
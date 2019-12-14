<?php

namespace XiaoLaoMen\Captcha\Captcha;

class Captcha
{
    // 验证码字符集合
    protected $codeSet = '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY';
    protected $fontSize;
    protected $useCurve;
    protected $useNoise;
    protected $useImgBg;
    protected $length;
    protected $type;
    protected $imageW;
    protected $imageH;
    protected $path;
    protected $ttfPath;

    protected function generate($type='math',$length=5,$codeSet)
    {
        $bag = '';
        switch ($type)
        {
            case 'math':
                $x   = random_int(10, 30);
                $y   = random_int(1, 9);
                $bag = "{$x} + {$y} = ";
                $key = $x + $y;
                $key .= '';
                $math=true;
                break;
            default:
                $characters = str_split($codeSet);
                for ($i = 0; $i < $length; $i++) {
                    $bag .= $characters[random_int(0, count($characters) - 1)];
                }

                $key = mb_strtolower($bag, 'UTF-8');

                $math=false;
                break;
        }
        return [
            'value' => $bag,
            'key'=>$key,
            'math'=>$math,
        ];
    }


    /**
     * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数)
     *
     *      高中的数学公式咋都忘了涅，写出来
     *        正弦型函数解析式：y=Asin(ωx+φ)+b
     *      各常数值对函数图像的影响：
     *        A：决定峰值（即纵向拉伸压缩的倍数）
     *        b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
     *        φ：决定波形与X轴位置关系或横向移动距离（左加右减）
     *        ω：决定周期（最小正周期T=2π/∣ω∣）
     *
     */
    protected function writeCurve($im,$imageW,$imageH,$fontSize,$color)
    {
        $px = $py = 0;
        // 曲线前部分
        $A = mt_rand(1, $imageH / 2); // 振幅
        $b = mt_rand(-$imageH / 4, $imageH / 4); // Y轴方向偏移量
        $f = mt_rand(-$imageH / 4, $imageH / 4); // X轴方向偏移量
        $T = mt_rand($imageH, $imageW * 2); // 周期
        $w = (2 * M_PI) / $T;
        $px1 = 0; // 曲线横坐标起始位置
        $px2 = mt_rand(intval(round(floatval($imageW / 2))), intval(round(floatval($imageW * 0.8)))); // 曲线横坐标结束位置

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $imageH / 2; // y = Asin(ωx+φ) + b
                $i  = (int) ($fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($im, $px + $i, $py + $i, $color);
                    $i--;
                }
            }
        }
        // 曲线后部分
        $A   = mt_rand(1, $imageH / 2); // 振幅
        $f   = mt_rand($imageH / 4, $imageH / 4); // X轴方向偏移量
        $T   = mt_rand($imageH, $imageW * 2); // 周期
        $w   = (2 * M_PI) / $T;
        $b   = $py - $A * sin($w * $px + $f) - $imageH / 2;
        $px1 = $px2;
        $px2 = $imageW;
        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $imageH / 2; // y = Asin(ωx+φ) + b
                $i  = (int) ($fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($im, $px + $i, $py + $i, $color);
                    $i--;
                }
            }
        }
    }

    /**
     * 画杂点
     * 往图片上写不同颜色的字母或数字
     */
    protected function writeNoise($im,$imageW,$imageH)
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < 10; $i++) {
            //杂点颜色
            $noiseColor = imagecolorallocate($im, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring($im, 5, mt_rand(-10, $imageW), mt_rand(-10, $imageH), $codeSet[mt_rand(0, 29)], $noiseColor);
            }
        }
    }

    /**
     * 绘制背景图片
     * 注：如果验证码输出图片比较大，将占用比较多的系统资源
     */
    protected function background($im,$imageW,$imageH)
    {
        $path = __DIR__ . '/assets/bgs/';
        $dir  = dir($path);
        $bgs = [];
        while (false !== ($file = $dir->read())) {
            if ('.' != $file[0] && substr($file, -4) == '.jpg') {
                $bgs[] = $path . $file;
            }
        }
        $dir->close();
        $gb = $bgs[array_rand($bgs)];
        list($width, $height) = @getimagesize($gb);
        $bgImage = @imagecreatefromjpeg($gb);

        @imagecopyresampled($im, $bgImage, 0, 0, 0, 0, $imageW, $imageH, $width, $height);
        $return = $bgImage;
        @imagedestroy($bgImage);
        return $return;

    }


    public function __construct()
    {
        $this->fontSize = config('captcha.config.fontSize');
        $this->useCurve = config('captcha.config.useCurve');
        $this->useNoise = config('captcha.config.useNoise');
        $this->useImgBg = config('captcha.config.useImgBg');
        $this->length = config('captcha.config.length');
        $this->type = config('captcha.config.type');
        $this->imageW = config('captcha.config.imageW');
        $this->imageH = config('captcha.config.imageH');
        $this->path = config('captcha.config.path');
        $this->ttfPath = config('captcha.config.ttfPath');
    }


    public function create()
    {
        // 背景颜色
        $bg = [243, 251, 254];
        $generator = $this->generate($this->type,$this->length,$this->codeSet);
        $math = $generator['math'];

        // 建立一幅图像
        $im = imagecreate($this->imageW, $this->imageH);

        // 设置背景
        imagecolorallocate($im, $bg[0], $bg[1], $bg[2]);

        // 验证码字体随机颜色
        $color = imagecolorallocate($im, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));

        // 验证码使用随机字体


        if (empty($fontttf)) {
            $dir  = opendir ($this->ttfPath);
            $ttfs = [];
            while (false !== ($file = readdir($dir))) {
                if ('.' != $file[0] && substr($file, -4) == '.ttf') {
                    $ttfs[] = $file;
                }
            }
            $fontttf = $ttfs[array_rand($ttfs)];
        }

        $fontttf = $this->ttfPath . $fontttf;

        if ($this->useImgBg) {
            $this->background($im,$this->imageW,$this->imageH);
        }

        if ($this->useNoise) {
            // 绘杂点
            $this->writeNoise($im,$this->imageW,$this->imageH);
        }

        if ($this->useCurve) {
            // 绘干扰线
            $this->writeCurve($im,$this->imageW,$this->imageH,$this->fontSize,$color);
        }

        // 绘验证码
        $text =str_split($generator['value']);

        foreach ($text as $index => $char) {

            $x     = $this->fontSize * ($index + 1) * mt_rand(1.2, 1.6) * ($math ? 1 : 1.5);
            $y     = $this->fontSize + mt_rand(10, 20);

            $angle = $math ? 0 : mt_rand(-40, 40);

            imagettftext($im, $this->fontSize, $angle, $x, $y, $color, $fontttf, $char);
        }
        $name = md5(time().uniqid());

        $new = $this->path.$name.'.png';

        $result = imagepng($im,$new,1);

        imagedestroy($im);

        if($result)
        {
            $generator['url']=$new;
            return $generator;
        }
        return false;
    }
}
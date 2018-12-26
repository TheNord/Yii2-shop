<?php

namespace shop\services;

use PHPThumb\GD;
use Yii;

class WaterMarker
{
    private $width;
    private $height;
    private $watermark;

    public function __construct($width, $height, $watermark)
    {
        $this->width = $width;
        $this->height = $height;
        $this->watermark = $watermark;
    }

    public function process(GD $thumb): void
    {
        $watermark = new GD(Yii::getAlias($this->watermark));

        $source = $watermark->getOldImage();

        // настраиваем адаптивные размеры
        if (!empty($this->width) || !empty($this->height)) {
            $thumb->adaptiveResize($this->width, $this->height);
        }

        // получаем текущие размеры превьюшки и вотемарка
        $originalSize = $thumb->getCurrentDimensions();
        $watermarkSize = $watermark->getCurrentDimensions();

        // считаетм расстояние на которое нужно сдвинуть
        // отнимаем от оригинального размера, размер вотермарка с -10
        // врезультате она окажется в правом нижнем углу с отступом 10px
        $destinationX = $originalSize['width'] - $watermarkSize['width'] - 10;
        $destinationY = $originalSize['height'] - $watermarkSize['height'] - 10;

        // указываем куда будем применять вотемарку
        $destination = $thumb->getOldImage();

        // настраиваем прозрачность
        imagealphablending($source, true);
        imagealphablending($destination, true);

        // копируем вотемарку на нужные координаты и устанавливаем размеры
        imagecopy(
            $destination,
            $source,
            $destinationX, $destinationY,
            0, 0,
            $watermarkSize['width'], $watermarkSize['height']
        );

        // подменяем изображения
        $thumb->setOldImage($destination);
        $thumb->setWorkingImage($destination);
    }
}
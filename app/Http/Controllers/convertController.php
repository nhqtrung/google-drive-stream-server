<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use FFMpeg;
use FFMpeg\Format\Video\X264;

class convertController extends Controller
{
    public function testConvert() {
        $lowBitrateFormat  = (new X264('libmp3lame'))->setKiloBitrate(500);
        $midBitrateFormat  = (new X264('libmp3lame'))->setKiloBitrate(1500);
        $highBitrateFormat = (new X264('libmp3lame'))->setKiloBitrate(3000);

        // open the uploaded video from the right disk...
        FFMpeg::fromDisk('local')
            ->open('01.mp4')

        // call the 'exportForHLS' method and specify the disk to which we want to export...
            ->exportForHLS()
            ->onProgress(function ($percentage) {
                echo "$percentage % transcoded";
            })
            ->toDisk('local')

        // we'll add different formats so the stream will play smoothly
        // with all kinds of internet connections...
            ->addFormat($lowBitrateFormat)
            ->addFormat($midBitrateFormat)
            ->addFormat($highBitrateFormat)

        // call the 'save' method with a filename...
            ->save('02.m3u8');
    }
}

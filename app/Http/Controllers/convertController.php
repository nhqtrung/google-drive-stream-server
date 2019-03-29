<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use SergeyHartmann\StreamingLinkGenerator\Generator;
use SergeyHartmann\StreamingLinkGenerator\StreamingLink;
use SergeyHartmann\StreamingLinkGenerator\CookieLoader\SimpleCookieLoader;

class convertController extends Controller
{
    public function testConvert() {
        $lowBitrateFormat  = (new X264('libmp3lame'))->setKiloBitrate(500);
        $midBitrateFormat  = (new X264('libmp3lame'))->setKiloBitrate(1500);
        $highBitrateFormat = (new X264('libmp3lame'))->setKiloBitrate(3000);

        // open the uploaded video from the right disk...
        FFMpeg::fromDisk('videos')
            ->open('KM-PN09.mp4')

        // call the 'exportForHLS' method and specify the disk to which we want to export...
            ->exportForHLS()
            ->onProgress(function ($percentage) {
                dump("$percentage % transcoded");
            })
            ->toDisk('converted_videos')

        // we'll add different formats so the stream will play smoothly
        // with all kinds of internet connections...
            ->addFormat($lowBitrateFormat)
            ->addFormat($midBitrateFormat)
            ->addFormat($highBitrateFormat)

        // call the 'save' method with a filename...
            ->save('EncryptedDocument_T5.m3u8');
        return response('export for HLS completed');
    }

    public function getFile() {
        $hls_playlist = Storage::disk('converted_videos')->files();
        $storagePath = Storage::disk('converted_videos')->getAdapter()->getPathPrefix();
        foreach($hls_playlist as $item) {
            $itemPath = $storagePath.$item;

            $fileData = File::get($itemPath);

            Storage::cloud()->put($item, $fileData);
        }
    }

    public function getFileInFolder() {
        // The human readable folder name to get the contents of...
        // For simplicity, this folder is assumed to exist in the root directory.
        $folder = 'Phim';

        // Get root directory contents...
        $contents = collect(Storage::cloud()->listContents('/', false));

        // Find the folder you are looking for...
        $dir = $contents->where('type', '=', 'dir')
            ->where('filename', '=', $folder)
            ->first(); // There could be duplicate directory names!

        if ( ! $dir) {
            return 'No such folder!';
        }

        // Get the files inside the folder...
        $files = collect(Storage::cloud()->listContents($dir['path'], false))
            ->where('type', '=', 'file');

        $listFile = $files->mapWithKeys(function($file) {
            $filename = $file['filename'].'.'.$file['extension'];
            $path = $file['path'];
            $parsePath = explode("/", $path);
            // dump($parsePath);
            $fileId = $parsePath[sizeof($parsePath) - 1];

            $cookieLoader = new SimpleCookieLoader(dirname(__FILE__) . '/g.cookie');
            $generator    = new Generator($cookieLoader);
            $streamingLink = $generator->generate($fileId);

            return [$filename => $streamingLink->getStreamingLink()];
        });
        $hls_playlist = Storage::disk('converted_videos')->files();
        $storagePath = Storage::disk('converted_videos')->getAdapter()->getPathPrefix();
        foreach($hls_playlist as $item) {
            $itemPath = $storagePath.$item;
            if (strpos($item, 'm3u8')) {
                $fileData = File::get($itemPath);
                foreach($listFile as $key => $value) {
                    $fileData = str_replace($key, $value, $fileData);
                }
                Storage::cloud()->put('01.m3u8', $fileData);
            }
        }
    }
}

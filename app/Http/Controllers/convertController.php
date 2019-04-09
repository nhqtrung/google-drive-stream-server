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
use App\Jobs\putFileInDirGoogleDrive;
use App\Jobs\rewriteM3U8File;

class convertController extends Controller
{
    private $generator;
    private $cookieLoader;
    public function __construct()
    {
        $this->cookieLoader = new SimpleCookieLoader(dirname(__FILE__) . '/g.cookie');
        $this->generator    = new Generator($this->cookieLoader);
    }

    public function showFormInfo() {
        // $this->dispatch(new putFileInDirGoogleDrive('Test/OHRORA/01', 'test.m3u8', 'Hello, Just test'));
        $fileInVideoDisk = Storage::disk('videos')->allFiles();
        return view('form-info', ['listFileInput' => $fileInVideoDisk]);
    }

    public function exportVideoForHls(Request $request) {
        $videoPath = $request['input-path'];
        $folder = $request['output-path'];
        $googleDriveFolder = $request['google-drive-folder'];

        $lowBitrateFormat  = (new X264('aac'))->setKiloBitrate(3000);
        $midBitrateFormat  = (new X264('aac'))->setKiloBitrate(4800);
        $highBitrateFormat = (new X264('aac'))->setKiloBitrate(8000);
        // open the uploaded video from the right disk...
        FFMpeg::fromDisk('videos')
            ->open($videoPath)
            ->addFilter(function ($filters) {
                $filters->resize(new \FFMpeg\Coordinate\Dimension(1920, 1080));
            })
            ->addFilter(function ($filters) {
                $watermarkPath = 'videos/vtc4.png';
                $filters->watermark($watermarkPath, [
                    'position' => 'relative',
                    'bottom' => 0,
                    'right' => 0,
                ]);
            })
            ->exportForHLS()
            ->onProgress(function ($percentage) {
                // echo "$percentage % transcoded";
            })
            ->toDisk('converted_videos')
            // ->addFormat($lowBitrateFormat)
            // ->addFormat($midBitrateFormat)
            ->addFormat($lowBitrateFormat)
            ->save($folder.'/EncryptedDocument_T5.m3u8');

        //change all file extension from ts to txt
        $hls_playlist = Storage::disk('converted_videos')->files($folder);
        $storagePath = Storage::disk('converted_videos')->getAdapter()->getPathPrefix();
        foreach($hls_playlist as $file) {
            $file = str_replace('/', '\\', $file);
            $fileInfo = explode('\\', $file);
            $fileInfo = $fileInfo[sizeof($fileInfo) - 1];
            $fileInfo = explode(".", $fileInfo);
            $fileName = $fileInfo[sizeof($fileInfo) - 2];
            $fileExtension = $fileInfo[sizeof($fileInfo) - 1];

            if ($fileExtension == 'ts') {
                $fileExtension = 'txt';
                $filePath = $storagePath.$file;
                $relativeFileName = $fileName.'.'.$fileExtension;
                putFileInDirGoogleDrive::dispatch($googleDriveFolder, $relativeFileName, $filePath);
            }

            if ($fileExtension == 'm3u8') {
                $filePath = $storagePath.$file;
                $fileData = File::get($filePath);
                if (strpos($fileData, "ts")) {
                    $newFileData = str_replace("ts", "txt", $fileData);
                    $relativeFileName = $fileName.'.'.$fileExtension;
                    Storage::disk('converted_videos')->put($file, $newFileData);
                }
            }
        }

        rewriteM3U8File::dispatch($folder, $googleDriveFolder);

        return "File $videoPath export for HLS Completed";



    }

    public function getFile() {
        $hls_playlist = Storage::disk('converted_videos')->files("test/05");
        $storagePath = Storage::disk('converted_videos')->getAdapter()->getPathPrefix();
        foreach($hls_playlist as $item) {
            $itemPath = $storagePath.$item;
            $fileData = File::get($itemPath);
            

            $file = explode("/", $item);
            $filename = $file[sizeof($file) - 1];
            if (strpos($filename, 'txt')) {
                Storage::cloud()->put($filename, $fileData);
            }
        }
        return 'Put file to Google Drive Success';
    }

    public function getFileInFolder() {

    }
}

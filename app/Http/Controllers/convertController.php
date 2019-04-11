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
use App\Jobs\ConvertVideoForStreaming;
use App\Video;
use App\Export_progress;

class convertController extends Controller
{
    private $generator;
    private $cookieLoader;
    private $exportProgress, $video;
    public function __construct()
    {
        $this->cookieLoader = new SimpleCookieLoader(dirname(__FILE__) . '/g.cookie');
        $this->generator    = new Generator($this->cookieLoader);
        $this->exportProgress = new Export_progress;
        $this->video = new Video;
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

        $this->video->input_disk = 'videos';
        $this->video->output_disk = 'converted_videos';
        $this->video->input_path = $videoPath;
        $this->video->output_path = $folder;
        $this->video->watermark = $request->watermark;
        $this->video->stream_link = null;
        $this->video->google_drive_folder = $googleDriveFolder;
        $this->video->status = null;
        
        $this->video->save();

        $this->exportProgress->percentent_progress = 0;
        $this->exportProgress->idVideo = $this->video->id;
        $this->exportProgress->save();

        ConvertVideoForStreaming::dispatch($this->video, $this->exportProgress);

        return response()->json(['video_id' => $this->video->id, 'video_path' => $videoPath,'folder_path' => $folder ,'progress_id' => $this->exportProgress->id]);
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

    public function VideoExportProgressAPI($progressId) {
        $exportProgress = Export_progress::find($progressId);
        return response()->json(['videoId' => $exportProgress->idVideo, 'percentent_progress' => $exportProgress->percentent_progress]);
    }
}

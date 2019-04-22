<?php

namespace App\Jobs;

use App\Video;
use Carbon\Carbon;
use FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\putFileInDirGoogleDrive;
use App\Jobs\rewriteM3U8File;
use App\Export_progress;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Helpers\GoogleDriveHelper;

class ConvertVideoForStreaming implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $video, $export_progress, $googleDriveFolder;

    public function __construct(Video $video, Export_progress $export_progress)
    {
        $this->video = $video;
        $this->export_progress = $export_progress;
    }

    public function handle()
    {
        $mainDisk = 'google';
        $backupDisk = 'backup_google';
        $inputPath = $this->video->input_path;
        $folder = $this->video->output_path;
        $googleDriveFolder = $this->video->google_drive_folder;
        $googleDriveHelper = new GoogleDriveHelper;

        // create some video formats...
        $lowBitrateFormat  = (new X264('aac'))->setKiloBitrate(750);
        $midBitrateFormat  = (new X264('aac'))->setKiloBitrate(2400);
        $highBitrateFormat = (new X264('aac'))->setKiloBitrate(4200);

        $ffmpegExportHLS = FFMpeg::fromDisk('videos')
            ->open($inputPath)
            ->addFilter(function ($filters) {
                $filters->resize(new \FFMpeg\Coordinate\Dimension(854, 480));
            });

        if (!empty($this->video->watermark)) {
            $ffmpegExportHLS->addFilter(function ($filters) {
                $watermarkPath = $this->video->watermark;
                $filters->watermark($watermarkPath, [
                    'position' => 'relative',
                    'bottom' => 0,
                    'right' => 0,
                ]);
            });
        }


        $ffmpegExportHLS->exportForHLS()
            ->setSegmentLength(5)
            ->onProgress(function ($percentage) {
                $this->export_progress->percentent_progress = $percentage;
                $this->export_progress->save();
            })
            ->toDisk('converted_videos')
            ->addFormat($lowBitrateFormat)
            // ->addFormat($midBitrateFormat)
            // ->addFormat($lowBitrateFormat)
            ->save($folder.'/EncryptedDocument_T5.m3u8');

        $this->export_progress->percentent_progress = 100;
        $this->export_progress->save();

        $mainDiskFolderId = $googleDriveHelper->getFolderIdFromOriginalPath($googleDriveFolder, $mainDisk);
        $backupDiskFolderId = $googleDriveHelper->getFolderIdFromOriginalPath($googleDriveFolder, $backupDisk);

        $this->video->status = "Export for HLS Completed. On uploading google process";
        $this->video->save();
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
                putFileInDirGoogleDrive::dispatch($mainDiskFolderId, $relativeFileName, $filePath, $mainDisk);
                putFileInDirGoogleDrive::dispatch($backupDiskFolderId, $relativeFileName, $filePath, $backupDisk);
            }
            $this->video->status = "Upload all segment file TS to Google Drive";
            $this->video->save();
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

        rewriteM3U8File::dispatch($folder, $googleDriveFolder, $mainDisk);
        rewriteM3U8File::dispatch($folder, $googleDriveFolder, $backupDisk);
    }
}
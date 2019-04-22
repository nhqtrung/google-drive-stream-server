<?php 
namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

/**
* 
*/
class GoogleDriveHelper
{
	public function getFolderIdFromOriginalPath($path, $disk){
        $folderLevel = explode("/", $path);
        $previousDirectory = '/';
        $recursive = false; // Get subdirectories also?
        $previousContents = collect(Storage::disk($disk)->listContents($previousDirectory, $recursive));
        // dd($previousContents);
        for ($i = 0; $i < count($folderLevel); $i++) {
            $nextDirectory = $previousContents->where('type', '=', 'dir')
                                            ->where('filename', '=', $folderLevel[$i])
                                            ->first(); // There could be duplicate directory names!
            if ( ! $nextDirectory ) {
                $previousDirectoryPath = $previousDirectory['path'] ?? "" ;
                $folderPath = $previousDirectoryPath . '/' . $folderLevel[$i];

                Storage::disk($disk)->makeDirectory($folderPath);


                $previousContents = collect(Storage::disk($disk)->listContents($previousDirectoryPath, $recursive));
                $previousDirectory = $previousContents->where('type', '=', 'dir')
                                                ->where('filename', '=', $folderLevel[$i])
                                                ->first();
                $previousContents = collect(Storage::disk($disk)->listContents($previousDirectory['path'], $recursive));
            } else {
                $previousDirectory = $nextDirectory;
                $previousContents = collect(Storage::disk($disk)->listContents($previousDirectory['path'], $recursive));
            }
        }
        return $previousDirectory['path'];
    }
    
    public function getAllFileFromOriginnalPath($path, $disk){
        $folderLevel = explode('/', $path);
        $previousDirectory = '/';
        $recursive = false; // Get subdirectories also?
        $previousContents = collect(Storage::disk($disk)->listContents($previousDirectory, $recursive));

        for ($i = 0; $i < count($folderLevel); $i++) {
            $nextDirectory = $previousContents->where('type', '=', 'dir')
                            ->where('filename', '=', $folderLevel[$i])
                            ->first(); // There could be duplicate directory names!
            if ( ! $nextDirectory ) {
                return 'No directory name: '.$folderLevel[$i];
            } else {
                $previousDirectory = $nextDirectory;
                $previousContents = collect(Storage::disk($disk)->listContents($previousDirectory['path'], $recursive));
            }
        }

        return $previousContents->where('type', '=', 'file');
    }
}
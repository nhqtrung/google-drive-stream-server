<html>

<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
        crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
        crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
        crossorigin="anonymous"></script>
</head>

<body>
    <div class="container">
        <h2>Chọn File và folder lưu để bắt đầu export cho stream</h2>
        <form action="./" method="POST" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="form-group">
                <label>Input File</label>
                <select class="form-control" name="input-path" id="input-path">
                    @foreach ($listFileInput as $filePath)
                        <option value="{{ $filePath }}">{{ $filePath }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Output Folder</label>
                <input type="text" class="form-control" name="output-path" id="output-path">
            </div>

            <div class="form-group">
                <label>Google Drive Folder</label>
                <input type="text" class="form-control" name="google-drive-folder" id="google-drive-folder">
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    <script>
        $('#input-path').on('change', function() {
            
            let path = $(this).val();
            path = path.split('/');
            let filename = path[path.length - 1].split('.');
            let recommendFolderName = '';
            for (let i=0; i < path.length - 1; i++) {
                recommendFolderName += path[i] + '/';
            }
            for (let j=0; j < filename.length - 1; j++) {
                recommendFolderName += filename[j];
            }
            $('#output-path').val(recommendFolderName);
            $('#google-drive-folder').val(recommendFolderName);
        })
    </script>
</body>
</html
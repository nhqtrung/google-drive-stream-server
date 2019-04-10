<html>

<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
        crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
        crossorigin="anonymous"></script>
</head>

<body>
    <style>
        .progress {
            position: relative;
            width: 400px;
            border: 1px solid #ddd;
            padding: 1px;
            border-radius: 3px;
            height: 28px;
        }
        .bar {
            background-color: #B4F5B4;
            width: 0%;
            height: 28px;
            border-radius: 3px;
        }
        .percent {
            position: absolute;
            display: inline-block;
            top: 3px;
            left: 48%;
        }
    </style>
    <div class="container">
        <h2>Chọn File và folder lưu để bắt đầu export cho stream</h2>
        <form action="./" method="POST" enctype="multipart/form-data" id="video-info-form">
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

            <div class="form-group">
                <label>watermark</label>
                <input type="text" class="form-control" name="watermark" id="watermark">
            </div>            

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        <div class="progress">
            <div class="bar"></div>
            <div class="percent">0%</div>
        </div>
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

         $('#video-info-form').on('submit', function(event) {
            var processId;
            event.preventDefault();

            console.log($(this).serialize());
            (async function(serializeForm) {
                processId = await $.post('./', serializeForm, function(data) {
                    return data;
                });
                let updateProgressBar = setInterval(async function() {
                    let data = await $.get('./api/export-progress/' + processId, function(data) {
                        return data;
                    });
                    $('.bar').width(data.percentent_progress + '%');
                    $('.percent').html(data.percentent_progress + '%');
                    if (data.percentent_progress >= 100) {
                        clearInterval(updateProgressBar);
                    }
                }, 1000)
            })($(this).serialize());

        }) 
    </script>


</body>
</html
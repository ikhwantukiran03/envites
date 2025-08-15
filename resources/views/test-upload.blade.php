<!DOCTYPE html>
<html>
<head>
    <title>Test Supabase Upload</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test Supabase File Upload</h1>
    
    <form id="uploadForm" enctype="multipart/form-data">
        <div>
            <label for="file">Choose file:</label>
            <input type="file" id="file" name="file" required>
        </div>
        <div style="margin-top: 10px;">
            <label for="directory">Directory (optional):</label>
            <input type="text" id="directory" name="directory" placeholder="uploads" value="uploads">
        </div>
        <div style="margin-top: 10px;">
            <button type="submit">Upload File</button>
        </div>
    </form>

    <div id="result" style="margin-top: 20px;"></div>

    <script>
        // Set up CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData();
            formData.append('file', $('#file')[0].files[0]);
            formData.append('directory', $('#directory').val());
            
            $('#result').html('<p>Uploading...</p>');
            
            $.ajax({
                url: '/api/files/upload',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        $('#result').html(`
                            <div style="color: green;">
                                <h3>Upload Successful!</h3>
                                <p><strong>File Path:</strong> ${response.data.path}</p>
                                <p><strong>File URL:</strong> <a href="${response.data.url}" target="_blank">${response.data.url}</a></p>
                                <p><strong>Original Name:</strong> ${response.data.original_name}</p>
                                <p><strong>Size:</strong> ${response.data.size} bytes</p>
                                <p><strong>Type:</strong> ${response.data.mime_type}</p>
                                ${response.data.mime_type.startsWith('image/') ? 
                                    `<img src="${response.data.url}" style="max-width: 300px; margin-top: 10px;" alt="Uploaded image">` : 
                                    ''
                                }
                            </div>
                        `);
                    } else {
                        $('#result').html('<div style="color: red;">Upload failed: ' + response.message + '</div>');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Upload failed';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    $('#result').html('<div style="color: red;">' + errorMsg + '</div>');
                }
            });
        });
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload CSV</title>
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
    <div class="w-full h-screen flex items-center justify-center">
        <div class="w-3/4 flex flex-col justify-center">
        <h1 class="text-6xl font-extrabold tracking-wide leading-snug">Check your domains <span class="text-blue-500">availability & authority</span> easily, through a csv</h1>
            <form id="upload_form" action="/upload/do_upload" method="post" enctype="multipart/form-data">
                <div class="border border-dashed border-gray-500 relative">
                    <input type="file" accept=".csv" name="userfile" class="cursor-pointer relative block opacity-0 w-full h-full p-20 z-50">
                    <div class="text-center p-10 absolute top-0 right-0 left-0 m-auto">
                        <h4>
                            Drop files anywhere to upload
                            <br/>or
                        </h4>
                        <p class="">Select Files</p>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById("upload_form").onchange = function() {
            // submitting the form
            document.getElementById("upload_form").submit();
        };
    </script>
</body>
</html>
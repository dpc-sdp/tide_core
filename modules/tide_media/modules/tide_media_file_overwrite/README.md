# tide_media_file_overwrite
Enables an option on the edit media form to overwrite an existing file's name on upload.  

Note: The overwrite file upload option field will always reset to the global setting. 
This setting does not get saved against each file.

## Purpose
- media

## Module Configuration

Global configuration via: <pre>/admin/config/tide_media_file_overwrite/settings</pre>

1. Overwrite upload file if the same file name exists?: Gives the author option to overwrite file, and keeping the same filename when upload. Only apply to edit operation.
2. Media form field map: Enter the media bundle machine name and their corresponding field machine name of type file or image in json format. 
For example: if you have a media bundle name *Document* and its machine name is *document*, it has the field name Document and the field machine name is *field_document* with the type of *file*. Enter: <code>{"document":"field_document"}</code>

Sample form field map config:

<pre>
{"audio":"field_media_file","document":"field_media_file","image":"field_media_image","embedded_video":"field_media_video_embed_field","file":"field_media_file","video":"field_media_file"}
</pre>

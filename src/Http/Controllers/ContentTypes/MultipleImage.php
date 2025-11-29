<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class MultipleImage extends BaseType
{
    /**
     * @return string
     */
    public function handle()
    {
        $filesPath = [];
        $files = $this->request->file($this->row->field);

        if (!$files) {
            return;
        }

        $manager = ImageManager::gd();

        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $image = $manager->read($file->getPathname())->orient();

            $resize_width = null;
            $resize_height = null;

            if (isset($this->options->resize) && (
                isset($this->options->resize->width) || isset($this->options->resize->height)
            )) {
                if (isset($this->options->resize->width) && $this->options->resize->width != null && $this->options->resize->width != 'null') {
                    $resize_width = intval($this->options->resize->width);
                }
                if (isset($this->options->resize->height) && $this->options->resize->height != null && $this->options->resize->height != 'null') {
                    $resize_height = intval($this->options->resize->height);
                }
            } else {
                $resize_width = $image->width();
                $resize_height = $image->height();
            }

            $resize_quality = intval($this->options->quality ?? 75);

            $filename = Str::random(20);
            $path = $this->slug.DIRECTORY_SEPARATOR.date('FY').DIRECTORY_SEPARATOR;
            array_push($filesPath, $path.$filename.'.'.$file->getClientOriginalExtension());
            $filePath = $path.$filename.'.'.$file->getClientOriginalExtension();

            $image = $image->scale(
                width: $resize_width,
                height: $resize_height
            );

            $encoded = $this->encodeForExtension($image, $file->getClientOriginalExtension(), $resize_quality);

            Storage::disk(config('voyager.storage.disk'))->put($filePath, $encoded, 'public');

            if (isset($this->options->thumbnails)) {
                foreach ($this->options->thumbnails as $thumbnails) {
                    if (isset($thumbnails->name) && isset($thumbnails->scale)) {
                        $scale = intval($thumbnails->scale) / 100;
                        $thumb_resize_width = $resize_width;
                        $thumb_resize_height = $resize_height;

                        if ($thumb_resize_width != null && $thumb_resize_width != 'null') {
                            $thumb_resize_width = $thumb_resize_width * $scale;
                        }

                        if ($thumb_resize_height != null && $thumb_resize_height != 'null') {
                            $thumb_resize_height = $thumb_resize_height * $scale;
                        }

                        $thumb = $manager->read($file->getPathname())
                            ->orient()
                            ->scale(
                                width: $thumb_resize_width,
                                height: $thumb_resize_height
                            );

                        $encodedThumb = $this->encodeForExtension($thumb, $file->getClientOriginalExtension(), $resize_quality);
                    } elseif (isset($this->options->thumbnails) && isset($thumbnails->crop->width) && isset($thumbnails->crop->height)) {
                        $crop_width = $thumbnails->crop->width;
                        $crop_height = $thumbnails->crop->height;
                        $thumb = $manager->read($file->getPathname())
                            ->orient()
                            ->cover($crop_width, $crop_height);

                        $encodedThumb = $this->encodeForExtension($thumb, $file->getClientOriginalExtension(), $resize_quality);
                    }

                    Storage::disk(config('voyager.storage.disk'))->put(
                        $path.$filename.'-'.$thumbnails->name.'.'.$file->getClientOriginalExtension(),
                        $encodedThumb,
                        'public'
                    );
                }
            }
        }

        return json_encode($filesPath);
    }

    private function encodeForExtension($image, $extension, $quality)
    {
        $ext = strtolower($extension);
        if ($ext === 'png') {
            return $image->toPng()->encode();
        }
        if ($ext === 'webp') {
            return $image->toWebp(quality: $quality)->encode();
        }
        return $image->toJpeg(quality: $quality)->encode();
    }
}

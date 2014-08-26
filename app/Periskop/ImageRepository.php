<?php namespace Periskop;

use Imagick;
use Illuminate\Support\Fluent;
use Illuminate\Filesystem\Filesystem;
use FilesystemIterator as FileIterator;

class ImageRepository {

    protected $filesystem;

    protected $upload_folder;

    protected $final_folder;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->upload_folder = '/' . trim(public_path('uploads'), '/') . '/';
        $this->final_folder = '/' . trim(public_path('images'), '/') . '/';
    }

    /**
     * Get all necessary information to an image
     *
     * @param   string  $path
     * @return  mixed          Illuminate\Support\Fluent | null
     */
    public function get($path)
    {
        // File not found
        if(!$this->filesystem->exists($path))
        {
            return null;
        }

        //filectime basename
        $image = new Imagick($path);

        $data = array(
            'timestamp' => $this->getCTime($path),
            'name'      => $this->getFilename($path),
            'url'       => asset('uploads/' . $this->getFilename($path)),
            'width'     => $image->getImageWidth(),
            'height'    => $image->getImageHeight(),
        );

        return new Fluent($data);
    }

    /**
     * Get the most recent uploaded file
     *
     * @return  mixed  Illuminate\Support\Fluent | null
     */
    public function getMostRecent()
    {
        $latest = null;
        $timestamp = 0;
        $files = new FileIterator($this->upload_folder, FileIterator::SKIP_DOTS);

        foreach ($files as $file)
        {
            // Only take the file if the 'last changed' date is newer
            // than the timestamp of our last ajax request
            // (which got saved in a cache file)
            if($file->getMTime() > $timestamp)
            {
                $latest = $file->getPathname();

                $timestamp = $file->getMTime();
            }
        }

        if($latest !== null)
        {
            return $this->get($latest);
        }

        return null;
    }

    public function moveFiles()
    {
        $files = $this->filesystem->files($this->upload_folder);

        foreach ($files as $file)
        {
            $this->filesystem->move($file, $this->final_folder . $this->getFilename($file));
        }
    }

    protected function getCTime($path)
    {
        return filectime($path);
    }

    protected function getFilename($path)
    {
        return basename($path);
    }

}
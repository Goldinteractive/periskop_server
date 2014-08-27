<?php namespace Periskop;

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

        $data = array(
            'timestamp' => $this->getCTime($path),
            'name'      => $this->getFilename($path),
            'url'       => asset('images/' . $this->getFilename($path)),
            'width'     => 3000,
            'height'    => 2000,
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

    public function getRandom()
    {
        $images = glob($this->final_folder . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        $rnd = $images[array_rand($images)]; // See comments

        return $this->get($rnd);
    }

    /**
     * Move all files from the upload folder
     * to the final folder
     *
     * @return void
     */
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
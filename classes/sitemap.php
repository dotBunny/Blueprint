<?php

namespace Blueprint;

class Sitemap
{
    protected $map = array();
    protected $outputFolder;
    protected $baseURI;


    public function AddOutput($outputPath)
    {
        if (!array_key_exists ( $outputPath , $this->map) )
        {
            $this->map[$outputPath] = array(
                    "ChangeFrequency" => "monthly",
                    "Priority" => "0.1",
                    "LastModified" => gmdate(DATE_W3C));
        }
    }

    public function SetOutputPath($path)
    {
        $this->outputFolder = $path;
    }
    public function SetBaseURI($URI)
    {
        $this->baseURI = $URI;
    }

    public function SetPriority($outputPath, $priority)
    {
        $this->map[$outputPath]["Priority"] = $priority;
    }
    public function SetLastModified($outputPath, $datestamp)
    {
        $this->map[$outputPath]["LastModified"] = $datestamp;
    }
    public function SetChangeFrequncy($outputPath, $frequency)
    {
        $this->map[$outputPath]["ChangeFrequency"] = $frequency;
    }


//

    public function Output()
    {
        $formattedDatestamp = gmdate(DATE_W3C);


        $data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
        foreach ($this->map as $outputPath => $item)
        {

            $cleanedPath = rtrim($outputPath, "index.html");
            $data .=
            "<url>
                <loc>" . $this->baseURI . $basePath . $cleanedPath . "</loc>
                <lastmod>" . $item["LastModified"] . "</lastmod>
                <changefreq>" . $item["ChangeFrequency"] . "</changefreq>
                <priority>" . $item["Priority"] . "</priority>
            </url>";
        }
        $data .= "</urlset>";


        Core::Output(INFO, "Building Sitemap @ " . $this->outputFolder . "...");



        // Write Base File
        file_put_contents(Core::BuildPath($this->outputFolder, "sitemap.xml"), $data);
        $file = fopen(Core::BuildPath($this->outputFolder, "sitemap.xml.gz"), "w");
        gzwrite($file, gzencode($data));
        fclose($file);

        // GZip File
    }
}
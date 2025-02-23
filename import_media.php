<!-- Import new videos -->
<?php
    function import_media()
    {
        $db = get_database();
        # Import new Videos
        $files = glob(get_root()."import/*.{mp4,webm,gif}", GLOB_BRACE);
        set_time_limit(1800);
        foreach ($files as $file)
        {
            $hash = md5_file($file);

            $query = $db->query("SELECT * FROM Metadata WHERE Hash = '" . $hash . "'");

            # Ugly but works
            $count = 0;
            foreach($query as $row)
            {
                $count += 1;
            }

            if ($count !== 0)
            {
                unlink($file);
                continue;
            }
            

            $thumbnailTime = '00:00:05';
            $command = "ffmpeg -ss $thumbnailTime -i $file -frames:v 1 -q:v 2 " . get_root() . '/videos/thumbnails/' . $hash . '.jpg';
            exec($command, $output, $return_var);

            $file_type = pathinfo($file, PATHINFO_EXTENSION);

            $stmt = $db->prepare("INSERT INTO Metadata (Hash, Datatype) VALUES (:hash, :file_type)");
            $stmt->bindParam(':hash', $hash);
            $stmt->bindParam(':file_type', $file_type);

            if ($stmt->execute()) 
            {
                exec("python3 vector_import.py ". $hash ." ". get_root() ."videos/thumbnails/$hash.jpg");
                rename($file, get_root()."videos/".$hash.".".$file_type);
            }
        }

        # Import new Images
        $files = glob(get_root()."import/*.{png,jpg,jpeg,PNG,JPG,JPEG}", GLOB_BRACE);
        set_time_limit(1800);
        foreach ($files as $file)
        {
            $hash = md5_file($file);

            $query = $db->query("SELECT * FROM Metadata WHERE Hash = '" . $hash . "'");

            # Ugly but works
            $count = 0;
            foreach($query as $row)
            {
                $count += 1;
            }

            if ($count !== 0)
            {
                unlink($file);
                continue;
            }

            $file_type = pathinfo($file, PATHINFO_EXTENSION);

            $stmt = $db->prepare("INSERT INTO Metadata (Hash, Datatype) VALUES (:hash, :file_type)");
            $stmt->bindParam(':hash', $hash);
            $stmt->bindParam(':file_type', $file_type);

            if ($stmt->execute()) 
            {
                #echo "Daten erfolgreich eingefügt!";
                $command = "magick '$file' -resize 200x300^ -gravity center -extent 200x300 '". get_root() ."pictures/thumbnails/$hash.jpg'";
                exec($command);
                rename($file, get_root()."pictures/".$hash.".".$file_type);
                exec("python3 vector_import.py ". $hash ." ". get_root() ."pictures/". $hash . ".". $file_type);
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="eng">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photos</title>
    <link rel="stylesheet" type="text/css" href="base.css">
    <link rel="stylesheet" type="text/css" href="index.css">
</head>
<body>
    <!-- Basic PHP functions -->
    <?php
        function get_root()
        {
            return  "./data/";
        }

        function get_database()
        {
            return new PDO('sqlite:database.db');
        }

        function check_database_query($query)
        {
            foreach($query as $row)
            {
                return true;
            }
            return false;
        }
    ?>

    <!-- Remove Media -->
    <?php
        if(isset($_POST["remove"]))
        {
            $db = get_database();

            try
            {   
                $db->beginTransaction();

                if(!isset($_POST["hash"]))
                {
                    throw new Exception();
                }

                $hash = $_POST["hash"];

                $stmt = $db->prepare("SELECT ID, Datatype FROM Metadata WHERE Hash = :hash");
                $stmt->bindParam(':hash', $hash);
                $stmt->execute();
                $query = $stmt->fetch(PDO::FETCH_ASSOC); 

                if(!$query)
                {
                    throw new Exception();
                }
                
                $id = $query["ID"];     
                $datatype =  $query["Datatype"];  

                $stmt = $db->prepare("DELETE FROM Catalog WHERE ID_Metadata = :ID");
                $stmt->bindParam(':ID', $id);
                $stmt->execute();
                $stmt = $db->prepare("DELETE FROM Vectors WHERE ID = :ID");
                $stmt->bindParam(':ID', $id);
                $stmt->execute();

                $stmt = $db->prepare("DELETE FROM Metadata WHERE ID = :ID");
                $stmt->bindParam(':ID', $id);
                $stmt->execute();
                
                if(file_exists(get_root() . "pictures/thumbnails/". $hash .".jpeg"))
                {
                    unlink(get_root() . "pictures/thumbnails/". $hash .".jpeg");
                }
                
                if(file_exists(get_root() . "pictures/". $hash .".jpeg"))
                {
                    unlink(get_root() . "pictures/". $hash .".jpeg");
                }

                $db->commit();
            }
            catch(Exception $e)
            {                
                $db->rollBack();
            }
        }
    ?>
    
    
    <!-- Main Page State Machine Functions --> 
    <?php 
        function print_search_results()
        {
            $db = get_database();

            # Shows wiki
            if(isset($_GET["wiki"]))
            {   
                $stmt = $db->prepare("SELECT Tag, COUNT(ID) AS 'Count' FROM Tags JOIN Catalog ON Catalog.ID_Tag = Tags.ID GROUP BY Tag ORDER BY Count DESC");
                $stmt->execute();
                $query = $stmt->fetchall(PDO::FETCH_ASSOC);

                foreach($query as $row)
                {
                    echo "<h4 class='wiki'><a href=index.php?tags=". $row['Tag'] .">". $row['Count'] . ' x ' . $row['Tag'] ."</a></h4>";
                }

                echo "
                <div class='form-container'>
                <form action='index.php' method='GET' class='form-container'>
                        <input type='hidden' name='tags' value='". $query[array_rand($query)]["Tag"]."'>
                        <input type='submit' class='random-button' value='Suprise 🎉'>
                </form>
                </div>";
                return;
            }

            if (!empty($_GET["tags"]) && isset($_GET["tags"]))
                {
                    # Query Tags
                    $query_intersect = "";
                    
                    $tags = explode(" ", $_GET['tags']);
                    rsort($tags);
                    foreach($tags as $tag)
                    {
                        if(empty($tag))
                        {
                            continue;
                        }
                        
                        if($tag[0] == '-')
                        {
                            if($query_intersect != "")
                            {
                                $query_intersect = $query_intersect . " EXCEPT ";
                            
                                $tag = substr($tag, 1, strlen($tag));
                                $query_intersect = $query_intersect ."SELECT ID_Metadata FROM Tags JOIN Catalog ON Tags.ID = Catalog.ID_Tag WHERE Tag = '". $tag . "' ";
                            }
                        }
                        else
                        {
                            if($query_intersect != "")
                            {
                                $query_intersect = $query_intersect . " INTERSECT ";
                            }
                            $query_intersect = $query_intersect ."SELECT ID_Metadata FROM Tags JOIN Catalog ON Tags.ID = Catalog.ID_Tag WHERE Tag = '". $tag . "' ";
                        }
                    }     
                    # Code so it shows results at random order
                    $query = $db->query("SELECT * FROM Metadata WHERE ID IN (". $query_intersect . ")");
                    $query = $query->fetchAll(PDO::FETCH_ASSOC);

                    while (count($query) > 0)
                    {   
                        $i = array_rand($query);
                        print_picture($query[$i]);
                        unset($query[$i]);
                    }
                }
            
            else
            {
                $db = get_database();
                $query = $db->query("SELECT * FROM Metadata WHERE ID NOT IN (SELECT ID_Metadata FROM Catalog)");
                $query = $query->fetchAll(PDO::FETCH_ASSOC);

                while (count($query) > 0)
                {   
                    $i = array_rand($query);
                    print_picture($query[$i]);
                    unset($query[$i]);
                }
            }    
        }

        function print_picture($row)
        {
            if(isset($_GET["tags"]))
            {
                $tags = $_GET["tags"];
            }
            else
            {
                $tags = "";
            }

            if (in_array($row["Datatype"], ["mp4", "webm", "gif", "mp4"]))
            {
                # Videos
                echo    '<a href="./index.php?hash='. $row["Hash"] . "&tags=" . $tags .'">
                            <div class="picture-box">
                                <img src="' . get_root() .'videos/thumbnails/' . $row["Hash"] . '.jpg' . '", alt="Error QwQ">
                            </div>
                        </a>';
            } 
            else
            {
                #Metadata
                echo    '<a href="./index.php?hash='. $row["Hash"] . "&tags=" . $tags . '">
                            <div class="picture-box">
                                <img src="' . get_root() .'pictures/thumbnails/' . $row["Hash"] . '.jpg' . '", alt="Picture not found!">
                            </div>
                        </a>';
            }
        }

        function print_picture_main()
        {
            if (isset($_GET["hash"]))
            {
                $db = get_database();
                $hash = $_GET["hash"];
                $query = $db->query("SELECT * FROM Metadata WHERE Hash = '" . $hash . "'");
                
                foreach ($query as $row)
                {   
                    if (in_array($row['Datatype'], ["mp4", "webm","gif","MP4"]))
                    {
                        # Videos
                        echo '<div class="picture-box-main">';
                        echo '<a href="videoplayer.php?file=' . $row["Hash"] . '.' . $row["Datatype"] .'">';
                        echo '<img src="' . get_root() . 'videos/thumbnails/' . $row["Hash"] . '.jpg' . '", alt="Sorry, the image was not found!">';
                        echo '</a>';
                        echo '</div>';
                        echo "<h4>&#9658 click thumbnail to play</h4>";
                    }
                    else
                    {
                        # Metadata
                        echo '<div class="picture-box-main">';
                        echo '<a href="' . get_root(). "pictures/" . $row["Hash"] . '.' . $row["Datatype"] .'">';
                        echo '<img src="' . get_root() . 'pictures/' . $row["Hash"] . '.' . $row["Datatype"] . '", alt="Sorry, the image was not found!">';
                        echo '</a>';
                        echo '</div>';
                        echo "<h4>&#9658 click thumbnail to see fullscreen</h4>";
    
                    }

                    $response = exec("python lens_search.py ". $_GET["hash"]);
                    $stmt = "SELECT Tag FROM Metadata JOIN Catalog ON Catalog.ID_Metadata = Metadata.ID JOIN Tags ON Tags.ID = Catalog.ID_Tag WHERE ";
                    $hashes = "";
                    foreach(explode(" ", $response) as $hash)
                    {   
                        if(!empty($hashes))
                        {
                            $hashes = $hashes . " OR ";
                        }
                        $hashes = $hashes. "Hash = '". $hash ."' ";
                    }
                    $stmt = $stmt. $hashes . " GROUP BY Tag HAVING COUNT(Tag) > 1";
                    $stmt = $stmt . " EXCEPT SELECT Tag FROM Metadata JOIN Catalog ON Catalog.ID_Metadata = Metadata.ID JOIN Tags ON Tags.ID = Catalog.ID_Tag WHERE Hash ='". $_GET["hash"]."'";

                    $db = get_database();
                    $query = $db->query($stmt);
                    echo "<h4>";
                    foreach($query as $tag)
                    {
                        echo $tag["Tag"]. " • ";
                    }
                    echo "</h4>";

                }
            }
        }

        function print_tags()
        {   
            if(isset($_GET["hash"]))
            {
                $db = get_database();
                
                $stmt = $db->prepare("SELECT Tag FROM Tags WHERE ID IN (SELECT ID_Tag FROM Catalog WHERE ID_Metadata IN (SELECT ID FROM Metadata WHERE Hash=:hash)) ORDER BY Tag");
                $stmt->execute([':hash' => $_GET["hash"]]);
                $query = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo "<div class='tags-container'><h3>";
                $first = true;
                foreach($query as $row)
                {      
                    if($first)
                    {
                        echo "• ";
                        $first = false;
                    }
                    echo "<a class='tags' href='./index.php?tags=". $row['Tag'] ."'>";
                    echo $row['Tag'];
                    echo "</a>";
                    echo " • ";
                }
                echo "</h3></div>";
            }
        }

        function print_tag_bar()
        {
            echo '
            <div class="bottom-bar">
                <div class="form-container">
                    <form action="./index.php?hash='. $_GET['hash'] .'&tags='. $_GET['tags'].'" method="POST" class="form-container">
                        <input type="submit" name="del" class="del-button" value="-">
                        <input type="text" name="edit" class="tags-text" placeholder="tags" required >
                        <input type="submit" name="add" class="add-button" value="+">
                    </form>
                </div>
            </div>
            ';
        }

        function print_search_bar()
        {  
            echo '
            <!-- Top bar -->
            <div class="top-bar">
                <div class="form-container">
                    <form action="./index.php" method="POST">
                ';
                       
            if (isset($_GET["hash"])) {
                echo "<input type='hidden' name='hash' value='". htmlspecialchars($_GET["hash"]) ."'>";
                echo "<input type='button' name='remove' value='🗑️' id='remove-button' class='remove-button' onclick='confirm_remove()'>";
            } else {
                echo "<input type='submit' value='Upload'>";
            }

            echo '  </form>
                    <form action="./index.php" method="GET">
                        ';
                       
            echo '
                        <input type="text" name="tags" placeholder="';

            if (isset($_GET["tags"])) {
                $tags = $_GET["tags"];
                if (!empty($tags)) {
                    echo htmlspecialchars($tags, ENT_QUOTES, 'UTF-8'); // prevent XSS by escaping the tags
                } else {
                    $db = get_database();
                    $query = $db->query("SELECT COUNT(ID) AS 'Count' FROM Metadata");

                    foreach ($query as $row) {
                        echo "search through " . $row['Count'] . " media";
                        break;
                    }
                }
            }

            echo '">
                        <input type="submit" id="search" value="Search 🔍">
                        <input type="submit" id="wiki" name="wiki" value="Wiki">
                    </form>
                </div>
            </div>';
        }
    ?>

        

    <!-- Import of new photos -->
    <?php
        $db = get_database();
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
                exec("python vector_import.py ". $hash ." ". get_root() ."pictures/". $hash . ".". $file_type);
            }
        }
    ?>

    <!-- Import new videos -->
    <?php
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
                exec("python vector_import.py ". $hash ." ". get_root() ."videos/thumbnails/$hash.jpg");
                rename($file, get_root()."videos/".$hash.".".$file_type);
            }
        }
    ?>

    <!-- Tag Handling -->
    <?php
        function tag_handling()
        {
            if(!empty($_GET['hash']) && !empty($_POST['edit']) && (!empty($_POST['add']) || !empty($_POST['del'])))
            {
                $db = get_database();

                # Check if picture exists
                $query = $db->query("SELECT * FROM Metadata WHERE Hash = '". $_GET['hash'] ."'");   
                
                if(!check_database_query($query))
                {
                    return;
                }
                

                # Delete
                if(isset($_POST['del']))
                {   
                    try 
                    {
                        $db->beginTransaction();
                    
                        $stmt = $db->prepare("
                            DELETE FROM Catalog 
                            WHERE ID_Metadata IN (SELECT ID FROM Metadata WHERE Hash = :hash) 
                            AND ID_Tag IN (SELECT ID FROM Tags WHERE Tag = :tags)
                        ");
                    
                        foreach (explode(" ", $_POST['edit']) as $tag)
                        {
                            // Execute the prepared statement for each tag
                            $stmt->execute([':hash' => $_GET['hash'], ':tags' => $tag]);
                        }
                    
                        // Commit the transaction
                        $db->commit();
                    } 
                    catch (Exception $e) 
                    {
                        $db->rollBack();
                    }
                    return;
                }        
                
                # Add
                if(isset($_POST['add']))
                {   
                    try 
                    {
                        $db->beginTransaction();
                    
                        foreach (explode(" ", $_POST['edit']) as $tag)
                        {
                            if(empty($tag))
                            {
                                continue;
                            }

                            
                            # Checks if tag already is in database
                            $stmt = $db->prepare("SELECT * FROM Tags WHERE Tag = :tag");
                            $stmt->execute([':tag' => $tag]);
                            $query = $stmt->fetchAll();

                            if(!check_database_query($query))
                            {
                                $stmt = $db->prepare("INSERT INTO Tags (Tag) VALUES (:tag)");
                                $stmt->execute([':tag' => $tag]);
                            }

                            # Checks if
                            $stmt = $db->prepare("SELECT * FROM Catalog WHERE ID_Metadata IN (SELECT ID FROM Metadata WHERE Hash = :hash) AND ID_Tag IN (SELECT ID FROM Tags WHERE Tag = :tag)");
                            $stmt->execute([':tag' => $tag, ':hash' => $_GET['hash']]);
                            $query = $stmt->fetchAll();

                            if(!check_database_query($query))
                            {
                                $stmt = $db->prepare("INSERT INTO Catalog (ID_Metadata, ID_Tag) VALUES ((SELECT ID FROM Metadata WHERE Hash = :hash), (SELECT ID FROM Tags WHERE Tag = :tag))");
                                $stmt->execute([':tag' => $tag, ':hash' => $_GET['hash']]);
                            }
                        }
                        $db->commit();
                        return;
                    } 
                    catch (Exception $e) 
                    {
                        $db->rollBack();
                    }

                    return;
                }      
            }
        }

        tag_handling();
    ?>
     
   <!-- Script Remove Button -->
   <script>
        let remove_pressed = false;

        function confirm_remove()
        {
            const button = document.getElementById("remove-button");

            if(!remove_pressed)
            {
                button.style.backgroundColor = "#d72848";
                button.value = "Confirm";
                remove_pressed = true;
                setTimeout(() => {
                    button.value = "Remove";
                    button.style.backgroundColor = "#61dc4e";
                    remove_pressed = false;
                }, 5000);
            }
            else
            {            
                console.log("GAY");
                button.type = "Submit";
            }
        }
    </script>


    <!-- Main Page -->
    <div class=container>
        <!-- Picture Box -->
        <div class="column">
        <div class="scrollbox">
        <div class="picture-grid-container">
            
            <?php 
                if(isset($_GET["hash"]) && isset($_GET["tags"]))
                {
                    echo "<h4>Search</h4>";
                    print_search_results();
                }
            ?> 
        </div>
        </div>
        </div>
        <div class="column column-middle"">
        <div class="scrollbox">
            <!-- Query and printing picture boxes main body -->
            <?php 
                if(isset($_GET["hash"]))
                {
                    echo "<div class='picture-grid-container-main'>";
                    print_search_bar();
                    print_tags();
                    print_picture_main();
                    print_tag_bar();
                }
                else
                {
                    echo "<div class='picture-grid-container'>";
                    print_search_bar();
                    print_search_results();
                }
            ?> 
        </div>
        </div>
        </div>
        <div class="column">
            <div class="scrollbox">
                <div class="picture-grid-container">
                    <?php
                        if (isset($_GET["hash"]))
                        {
                            echo "<h4>Similar</h4>";
                            $db = get_database();
                            $response = exec("python lens_search.py ". $_GET['hash']);
                            
                            if(strlen($response) != 0)
                            {
                                foreach (explode(" ", $response) as $hash)
                                {   $stmt = $db->prepare("SELECT * FROM Metadata WHERE Hash = :hash");
                                    $stmt->execute([':hash' => $hash]);
                                    $query = $stmt->fetchAll();                           
                                    print_picture($query[0]);
                                }
                            }
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>

     <!-- Java Script -->
     <script>
        let object = document.querySelector('.picture-box-main')

        if(object !== null)
        {
            object.addEventListener('mouseenter', function()
            {
                document.querySelector('.top-bar').style.opacity = '0.5';
                document.querySelector('.bottom-bar').style.opacity = '0.5';
            });

            object.addEventListener('mouseleave', function() 
            {
                document.querySelector('.top-bar').style.opacity = '1';
                document.querySelector('.bottom-bar').style.opacity = '0.9';
            });
        }
    </script>

     
</body>
</html>

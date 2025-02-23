    <!-- Main Page State Machine Functions --> 
    <?php 
        function print_search_results()
        {
            $db = get_database();

            # Shows wiki
            if(isset($_POST["wiki"]))
            {   
                $stmt = $db->prepare("SELECT Tag, COUNT(ID) AS 'Count' FROM Tags JOIN Catalog ON Catalog.ID_Tag = Tags.ID GROUP BY Tag ORDER BY Tag");
                $stmt->execute();
                $query = $stmt->fetchall(PDO::FETCH_ASSOC);
                
                foreach($query as $row)
                {
                    echo "<h4 class='wiki'><a href=index.php?tags=". $row['Tag'] .">". $row['Count'] . ' x ' . $row['Tag'] ."</a></h4>";
                }

                /*
                echo "
                <div class='form-container'>
                <form action='index.php' method='GET' class='form-container'>
                        <input type='hidden' name='tags' value='". $query[array_rand($query)]["Tag"]."'>
                        <input type='submit' class='random-button' value='Suprise 🎉'>
                </form>
                </div>";
                */
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

                if(count($query) == 0)
                {
                    echo " • NO TAGS • ";
                }
                else
                {
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
                }
                echo "</h3></div>";
            }
        }

        function print_tag_bar()
        {
            echo '
                <div class="form-container">
                    <form action="./index.php?hash='. $_GET['hash'] .'&tags='. $_GET['tags'].'" method="POST" class="form-container">
                        <input type="submit" name="del" class="del-button" value="-">
                        <input type="text" name="edit" oninput="tag_helper_edit()" id="input_edit" onfocus="showHelperBox()" onblur="hideHelperBox()" class="tags-text" placeholder="edit tags" required >
                        <input type="submit" name="add" class="add-button" value="+">
                    </form>
                </div>
            ';

        }

        function print_wiki_button()
        {
            if(!isset($_POST["wiki"]))
            {        
                echo "
                <form aciton='./index.php' method='POST' class='wiki-button-container'>
                    <button type='submit' class='wiki-button' name='wiki'><span class='material-symbols-outlined'>dictionary</span></button>
                </form>
                ";
            }
        }

        function print_search_bar_main()
        {  
            echo '
                <div class="form-container">
                    <form action="./index.php" method="POST">
                ';

            echo '  </form>
                    <form action="./index.php" method="GET">
                        ';
                       
            echo '
                        <input type="text" name="tags" oninput="tag_helper_search()" onfocus="showHelperBox()" onblur="hideHelperBox()" id="input_search" placeholder="';

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
                    </form>
                </div>';
        }

        function print_recommended_tags()
        {
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

            echo "<div class='recommended-tags-container'>";
            echo "<h4>Recommended</h4>";
            echo "<div class='recommended-tags-container-gird'>";

            foreach($query as $row)
            {   
                echo "<form action='./index.php?hash=". $_GET['hash'] ."&tags=". $_GET['tags']."' method='POST' class='form-container'>";
                echo "<input type='hidden' name='edit' value='". $row['Tag'] ."'>";
                echo "<input type='submit' class='recommended' name='add' value='". $row['Tag']  ."'>";
                echo "</form>";
            }
            echo "</div></div>";
        }


        function print_delete_button()
        {
            echo"
            <div class='delete-container'>
            <h3>WARNING</h3>
            <br>
            <h3>You can not undo this step and there wont be a confirmation!</h3>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <h3>Are you sure you want to delete this media?</h3>
            <br>
            <form action='./index.php?tags=' method='POST'>
            <input type='hidden' name='hash' value='". $_GET["hash"] ."'>
            <input type='submit' name='DELETE' class='delete-button' value='DELETE'>
            </div>
            ";

        }


        function delete_media()
        {
            if(isset($_POST["DELETE"]))
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

                    if(file_exists(get_root() . "videos/thumbnails/". $hash .".jpeg"))
                    {
                        unlink(get_root() . "videos/thumbnails/". $hash .".jpeg");
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
        }
    ?>

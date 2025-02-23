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
?>

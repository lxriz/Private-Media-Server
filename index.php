<!DOCTYPE html>
<html lang="eng">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viewer</title>

    <link rel="stylesheet" type="text/css" href="base_style.css">
    <link rel="stylesheet" type="text/css" href="index.css">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=dictionary" />
</head>
<body>
    <?php 
        include("base_functions.php");
        include("ui_logic.php");
        include("import_media.php");
        include("tag_handling.php");

        tag_handling();
        import_media();
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
                button.type = "Submit";
            }
        }
        

        // UI Handling Fade Top Bar
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
        <div class="column column-middle">
        <div class="scrollbox">
            <!-- Query and printing picture boxes main body -->
            <?php 
                if(isset($_GET["hash"]))
                {
                    echo "<div class='picture-grid-container-main'>";
                    print_search_bar_main();
                    print_tags();
                    print_picture_main();
                    print_tag_bar();
                    print_recommended_tags();
                    print_delete_button();
                }
                else
                {
                    print_search_bar_main();
                    delete_media();
                    echo "<div class='picture-grid-container'>";
                    print_search_results();
                    print_wiki_button();
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
                            $response = exec("python3 lens_search.py ". $_GET['hash']);
                            
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

    <textarea class="helper-box" id="helper-box" disabled></textarea>

    
    <script> 
        function tag_helper_search()
        {
            search = document.getElementById("input_search").value.trim();
            tag_helper(search);
        }

        function tag_helper_edit()
        {
            search = document.getElementById("input_edit").value.trim();
            tag_helper(search);
        }
        
        function tag_helper(word)
        {
            const words = word.split(/\s+/);
            document.getElementById("helper-box").value = "";

            if(words.length == 0)
            {
                return;
            }

            if(words[words.length - 1].startsWith('-'))
            {
                words[words.length - 1] = words[words.length - 1].substring(1);
            }
            
            fetch('./fetch_tags.php?tag=' + words[words.length - 1])
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                return response.json(); // Parse JSON data
            })
            .then(data => {
                const helperBox = document.getElementById("helper-box");
                // Loop through each row in the data
                for (let row of data) {
                    helperBox.value += row["Tag"] + " ["+ row["Count"] +"]" + "\n";
                }
            })
        }

        function hideHelperBox() 
        {
            const helperBox = document.getElementById("helper-box");
            helperBox.style.visibility = 'hidden'; // Hides the box
        }

        function showHelperBox() 
        {
            const helperBox = document.getElementById("helper-box");
            helperBox.style.visibility = 'visible'; // Makes the box visible again
        }
    </script>
</body>
</html>

<?php
    include("base_functions.php");
    
    if(empty($_GET["tag"]))
    {
        http_response_code(400);
        exit();
    }

    $db = get_database();

    $stmt = $db->prepare("SELECT Tag, COUNT(Catalog.ID_Tag) AS 'Count' FROM Tags JOIN Catalog ON Tags.ID = Catalog.ID_Tag WHERE Tag LIKE :tag GROUP BY Tag ORDER BY Count DESC");

    $stmt->bindValue(':tag', '%' . $_GET["tag"] . '%', PDO::PARAM_STR);

    $stmt->execute();

    $query = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($query)
    {
        echo json_encode($query, JSON_UNESCAPED_UNICODE);
    }
    
?>
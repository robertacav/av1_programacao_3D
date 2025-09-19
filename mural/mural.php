<?php
include "conexao.php";

// Verificar se as variáveis do Cloudinary estão definidas
if (!isset($cloud_name, $api_key, $api_secret)) {
    die("Variáveis do Cloudinary não configuradas. Verifique o arquivo conexao.php");
}

// Inserir novo produto
if(isset($_POST['cadastra'])){
    $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
    $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
    $preco = floatval($_POST['preco']);
    $imagem_url = "";
    $upload_sucesso = false;

    // Upload da imagem para o Cloudinary
    if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0){
        $cfile = new CURLFile($_FILES['imagem']['tmp_name'], $_FILES['imagem']['type'], $_FILES['imagem']['name']);
        $timestamp = time();
        $string_to_sign = "api_key=$api_key&timestamp=$timestamp$api_secret";
        $signature = sha1($string_to_sign);
        
        $data = [
            'file' => $cfile,
            'timestamp' => $timestamp,
            'api_key' => $api_key,
            'signature' => $signature
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/$cloud_name/image/upload");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        
        if($response === false){
            $error_msg = "Erro no cURL: " . curl_error($ch);
            curl_close($ch);
            die($error_msg);
        }
        
        curl_close($ch);
        $result = json_decode($response, true);
        
        if(isset($result['secure_url'])){
            $imagem_url = $result['secure_url'];
            $upload_sucesso = true;
        } else {
            die("Erro no upload: " . print_r($result, true));
        }
    } else {
        die("Erro no upload do arquivo: " . $_FILES['imagem']['error']);
    }
    
    // Inserir no banco de dados apenas se o upload foi bem-sucedido
    if($upload_sucesso){
        $sql = "INSERT INTO produtos (nome, descricao, preco, imagem_url) VALUES ('$nome', '$descricao', $preco, '$imagem_url')";
        if(mysqli_query($conexao, $sql)){
            header("Location: mural.php");
            exit;
        } else {
            die("Erro ao inserir no banco de dados: " . mysqli_error($conexao));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8"/>
    <title>Mural de Produtos</title>
    <link rel="stylesheet" href="mural.css"/>
    <script src="scripts/jquery.js"></script>
    <script src="scripts/jquery.validate.js"></script>
    <script>
    $(document).ready(function() {
        $("#mural").validate({
            rules: {
                nome: { required: true, minlength: 4 },
                descricao: { required: true, minlength: 10 },
                preco: { required: true, number: true, min: 0.01 }
            },
            messages: {
                nome: { 
                    required: "Digite o nome do produto", 
                    minlength: "O nome deve ter no mínimo 4 caracteres" 
                },
                descricao: { 
                    required: "Digite a descrição do produto", 
                    minlength: "A descrição deve ter no mínimo 10 caracteres" 
                },
                preco: { 
                    required: "Digite o preço do produto", 
                    number: "O preço deve ser um número válido",
                    min: "O preço deve ser maior que zero"
                }
            }
        });
    });
    </script>
</head>
<body>
<div id="main">
    <div id="geral">
        <div id="header">
            <h1>Mural de Produtos</h1>
        </div>

        <div id="formulario_mural">
            <form id="mural" method="post" enctype="multipart/form-data">
                <label>Nome do produto:</label>
                <input type="text" name="nome" required/>

                <label>Descrição:</label>
                <textarea name="descricao" required></textarea>

                <label>Preço:</label>
                <input type="number" step="0.01" name="preco" required/>

                <label>Imagem:</label>
                <input type="file" name="imagem" accept="image/*" required/>

                <input type="submit" value="Cadastrar Produto" name="cadastra" class="btn"/>
            </form>
        </div>

        <div class="produtos-container">
            <?php
            $seleciona = mysqli_query($conexao, "SELECT * FROM produtos ORDER BY id DESC");
            if(mysqli_num_rows($seleciona) > 0) {
                while($res = mysqli_fetch_assoc($seleciona)){
                    echo '<div class="produto">';
                    echo '<p><strong>ID:</strong> ' . $res['id'] . '</p>';
                    echo '<p><strong>Nome:</strong> ' . htmlspecialchars($res['nome']) . '</p>';
                    echo '<p><strong>Preço:</strong> R$ ' . number_format($res['preco'], 2, ',', '.') . '</p>';
                    echo '<p><strong>Descrição:</strong> ' . nl2br(htmlspecialchars($res['descricao'])) . '</p>';
                    echo '<img src="' . htmlspecialchars($res['imagem_url']) . '" alt="' . htmlspecialchars($res['nome']) . '">';
                    echo '</div>';
                }
            } else {
                echo '<p>Nenhum produto cadastrado ainda.</p>';
            }
            ?>
        </div>

        <div id="footer">
            <p>Mural de Produtos - Cloudinary & PHP</p>
        </div>
    </div>
</div>
</body>
</html>
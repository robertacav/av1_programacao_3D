<?php
include "conexao.php";

// Verificar se as variáveis do Cloudinary estão definidas
if (!isset($cloud_name, $api_key, $api_secret)) {
    die("Variáveis do Cloudinary não configuradas. Verifique o arquivo conexao.php");
}

// Função para deletar imagem do Cloudinary
function deletarImagemCloudinary($public_id, $cloud_name, $api_key, $api_secret) {
    $timestamp = time();
    $string_to_sign = "public_id=$public_id&timestamp=$timestamp$api_secret";
    $signature = sha1($string_to_sign);

    $data = [
        '$public_id' => $public_id,
        '$timestamp' => $timestamp,
        '$api_key' => $api_key,
        '$signature' => $signature
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/$cloud_name/image/destroy");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = "Erro cURL: " . curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

// Excluir produto
if (isset($_GET['acao']) && $_GET['acao'] == 'excluir') {
    $id = intval($_GET['id']);
    
    // Buscar informações do produto para excluir a imagem do Cloudinary
    $res = mysqli_query($conexao, "SELECT imagem_url FROM produtos WHERE id = $id");
    if ($res && mysqli_num_rows($res) > 0) {
        $dados = mysqli_fetch_assoc($res);
        
        if (!empty($dados['imagem_url'])) {
            $url = $dados['imagem_url'];
            $parts = explode("/", $url);
            $filename = end($parts);
            $public_id = pathinfo($filename, PATHINFO_FILENAME);
            
            // Deletar imagem do Cloudinary
            $resultado = deletarImagemCloudinary($public_id, $cloud_name, $api_key, $api_secret);
            
            if (isset($resultado['error'])) {
                die("Erro ao excluir imagem: " . $resultado['error']);
            }
        }
    }
    
    // Excluir o registro do banco de dados
    mysqli_query($conexao, "DELETE FROM produtos WHERE id = $id") or die("Erro ao excluir: " . mysqli_error($conexao));
    header("Location: moderar.php");
    exit;
}

// Atualizar produto
if (isset($_POST['atualiza'])) {
    $id = intval($_POST['id']);
    $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
    $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
    $preco = floatval($_POST['preco']);

    $sql = "UPDATE produtos SET nome='$nome', descricao='$descricao', preco=$preco WHERE id=$id";
    mysqli_query($conexao, $sql) or die("Erro ao atualizar: " . mysqli_error($conexao));
    header("Location: moderar.php");
    exit;
}

// Obter produto para edição
$editar_id = isset($_GET['acao']) && $_GET['acao'] == 'editar' ? intval($_GET['id']) : 0;
$produto_editar = null;

if ($editar_id) {
    $res = mysqli_query($conexao, "SELECT * FROM produtos WHERE id=$editar_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $produto_editar = mysqli_fetch_assoc($res);
    }
}

// Buscar todos os produtos
$produtos = mysqli_query($conexao, "SELECT * FROM produtos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8"/>
    <title>Moderar Produtos</title>
    <link rel = "stylesheet" href = "moderar.css">
</head>
<body>
<div id="main">
    <div id="header">
        <h1>Moderar Produtos</h1>
    </div>

    <?php if ($produto_editar): ?>
    <div id="formulario_edicao">
        <h2>Editando Produto: <?php echo htmlspecialchars($produto_editar['nome']); ?></h2>
        <form method="post">
            <label>Nome:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($produto_editar['nome']); ?>" required/>
            
            <label>Descrição:</label>
            <textarea name="descricao" required><?php echo htmlspecialchars($produto_editar['descricao']); ?></textarea>
            
            <label>Preço:</label>
            <input type="number" step="0.01" name="preco" value="<?php echo $produto_editar['preco']; ?>" required/>
            
            <input type="hidden" name="id" value="<?php echo $produto_editar['id']; ?>"/>
            <input type="submit" name="atualiza" value="Salvar Alterações"/>
            <a href="moderar.php">Cancelar</a>
        </form>
    </div>
    <?php endif; ?>

    <div class="produtos-container">
        <?php
        if (mysqli_num_rows($produtos) <= 0) {
            echo '<div>Nenhum produto cadastrado!</div>';
        } else {
            while ($produto = mysqli_fetch_assoc($produtos)) {
                echo '<div class="produto">';
                echo '<p><strong>ID:</strong> ' . $produto['id'] . '</p>';
                echo '<p><strong>Nome:</strong> ' . htmlspecialchars($produto['nome']) . '</p>';
                echo '<p><strong>Preço:</strong> R$ ' . number_format($produto['preco'], 2, ',', '.') . '</p>';
                echo '<p><strong>Descrição:</strong> ' . nl2br(htmlspecialchars($produto['descricao'])) . '</p>';
                
                if (!empty($produto['imagem_url'])) {
                    echo '<img src="' . htmlspecialchars($produto['imagem_url']) . '" alt="' . htmlspecialchars($produto['nome']) . '" width="200">';
                }
                
                echo '<div>';
                echo '<a href="moderar.php?acao=editar&id=' . $produto['id'] . '">Editar</a>';
                echo ' | ';
                echo '<a href="moderar.php?acao=excluir&id=' . $produto['id'] . '" onclick="return confirm(\'Tem certeza que deseja excluir este produto?\')">Excluir</a>';
                echo '</div>';
                echo '</div>';
                echo '<hr>';
            }
        }
        ?>
    </div>
</div>
</body>
</html>
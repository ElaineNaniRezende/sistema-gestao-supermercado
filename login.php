<!DOCTYPE html>
<html lang="pt-br">
    <?php
        // Configura fuso horário para America/São Paulo.
        date_default_timezone_set('America/Sao_Paulo')
    ?>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mercado</title>
        <link rel="stylesheet" href="css/estilo.css">
        <link rel="stylesheet" href="css/estiloConteudo.css">
    </head>
    <body>
        <div class="login">

            <?php
                //Verifica se há algum parâmetro chamado 'erroLogin' sendo recebido por GET
                if(isset($_GET['erroLogin'])){
                    $erroLogin = $_GET['erroLogin'];

                    if($erroLogin == 'dadosInvalidos'){
                        echo "<div class='alert alert-warning text-center'><strong>USUÁRIO ou SENHA</strong> inválidos!</div>";
                    }
                }
            ?>

            <h2>Login</h2>
            <form action="actionLogin.php" method="POST" class="was-validated">

                <label for="EMAIL">Email</label>
                <input type="email" id="EMAIL" name="EMAIL" placeholder="Ex. usuario@email.com" required style="width: 230px;">

                <label for="SENHA">Senha</label>
                <input type="password" id="SENHA" name="SENHA" minlength="8" maxlength="20" placeholder="********" required style="width: 230px;">

                <input type="submit" value="Login" style="width: 230px;">

            </form>
            <p style="text-align: center; margin-bottom: 5px;">Ainda não possui um cadastro? <a href="cadastrar.php">Clique aqui!</a></p>
        </div>
    </body>
</html>

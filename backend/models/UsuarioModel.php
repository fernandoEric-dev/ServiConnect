<?php
// backend/models/UsuarioModel.php

class UsuarioModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // 1. Verifica se CNPJ ou Email já existem
    public function existeUsuario(string $cpf_cnpj, string $email): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE cpf_cnpj = ? OR email = ?");
        $stmt->execute([$cpf_cnpj, $email]);
        return $stmt->rowCount() > 0;
    }


    // backend/models/UsuarioModel.php (Adicione este novo método à classe)

/**
 * READ: Busca todas as empresas com o papel 'terceirizada' para exibir no feed do Contratante.
 */
public function buscarTerceirizadas() {
    $sql = "
        SELECT 
            u.id as usuario_id,
            e.nome_empresa,
            e.descricao_servicos,
            e.area_atuacao,
            e.regioes_atendidas,
            e.foto_path
        FROM usuarios u
        JOIN empresas e ON u.id = e.usuario_id
        WHERE u.user_role = 'terceirizada'
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // 2. Insere na tabela 'usuarios' e retorna o ID
    public function cadastrarUsuario(array $data): ?int {
        // Certifique-se de que os nomes das colunas estão corretos:
        $sql = "INSERT INTO usuarios (cpf_cnpj, email, senha, tipo_conta) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        $success = $stmt->execute([
            $data['cpf_cnpj'],
            $data['email'],
            $data['senha'],
            $data['tipo_conta']
        ]);

        // Retorna o ID da última inserção se a operação foi bem-sucedida
        return $success ? $this->pdo->lastInsertId() : null;
    }

    // 3. Insere na tabela 'empresas'
    public function cadastrarEmpresa(array $data): bool {
        // Mapeamento dos campos do array para as colunas da tabela 'empresas'. 
        // Adapte esta query se os nomes das colunas da sua tabela 'empresas' forem diferentes!
        $sql = "INSERT INTO empresas (
                    usuario_id, nome, tipo_empresa, telefone, responsavel, descricao, 
                    regiao, horario, cep, logradouro, numero, complemento, bairro, cidade, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            $data['usuario_id'],
            $data['nome'],
            $data['tipo_empresa'],
            $data['telefone'],
            $data['responsavel'],
            $data['descricao'],
            $data['regiao'],
            $data['horario'],
            $data['cep'],
            $data['logradouro'],
            $data['numero'],
            $data['complemento'],
            $data['bairro'],
            $data['cidade'],
            $data['estado']

            
        ]);
        
    }
}
?>
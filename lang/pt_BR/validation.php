<?php

// Regras usadas pelos formulários de conta; as demais recorrem ao idioma de fallback.
return [
    'confirmed' => 'A confirmação de :attribute não confere.',
    'email' => 'Informe um endereço de e-mail válido.',
    'enum' => 'O valor selecionado em :attribute é inválido.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'in' => 'O valor selecionado em :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'lowercase' => 'O campo :attribute deve estar em letras minúsculas.',
    'max' => [
        'file' => 'O arquivo :attribute pode ter no máximo :max kilobytes.',
        'string' => 'O campo :attribute deve ter no máximo :max caracteres.',
    ],
    'min' => [
        'file' => 'O arquivo :attribute deve ter pelo menos :min kilobytes.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'not_regex' => 'O campo :attribute não pode conter marcação HTML.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_with' => 'O campo :attribute é obrigatório quando :values é informado.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',
    'uploaded' => 'O envio de :attribute falhou. O arquivo pode exceder o limite do servidor.',
    'current_password' => 'A senha informada está incorreta.',
    'password' => [
        'letters' => 'A senha deve conter pelo menos uma letra.',
        'mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
        'numbers' => 'A senha deve conter pelo menos um número.',
        'symbols' => 'A senha deve conter pelo menos um símbolo.',
        'uncompromised' => 'Esta senha apareceu em um vazamento de dados. Escolha outra senha.',
    ],

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'password_confirmation' => 'confirmação de senha',
        'current_password' => 'senha atual',
        'file' => 'imagem',
        'alt_text' => 'texto alternativo',
        'caption' => 'legenda',
        'credit' => 'crédito',
        'rights_type' => 'tipo de direito de uso',
        'rights_holder' => 'titular dos direitos',
        'license' => 'licença',
        'rights_notes' => 'observações sobre direitos',
        'reason' => 'motivo',
    ],
];

<?php

namespace App;

enum RoleCode: string
{
    case Collaborator = 'collaborator';
    case Author = 'author';
    case Editor = 'editor';
    case Moderator = 'moderator';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Collaborator => 'Colaborador',
            self::Author => 'Autor',
            self::Editor => 'Editor',
            self::Moderator => 'Moderador',
            self::Administrator => 'Administrador',
        };
    }
}

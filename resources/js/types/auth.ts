export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type RolGlobal = 'administrador' | 'miembro' | 'invitado';

export type Auth = {
    user: User;
    /**
     * Solo para ocultar controles de la interfaz. La autorización real vive en
     * las policies del servidor: ocultar un botón no es un permiso.
     */
    rolGlobal: RolGlobal | null;
    permisos: string[];
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

-- Datos de prueba para desarrollo
-- PostgreSQL 16

-- Usuario de prueba
INSERT INTO users (id, name, email, password, created_at, updated_at)
VALUES (
    '550e8400-e29b-41d4-a716-446655440000',
    'Usuario Demo',
    'demo@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
);

-- Clientes de prueba
INSERT INTO clientes (user_id, nombre, email, telefono, empresa, cargo, estado)
VALUES
    ('550e8400-e29b-41d4-a716-446655440000', 'Juan Pérez', 'juan.perez@ejemplo.com', '+54 11 1234-5678', 'Tech Solutions', 'CTO', 'activo'),
    ('550e8400-e29b-41d4-a716-446655440000', 'María García', 'maria.garcia@ejemplo.com', '+54 11 8765-4321', 'Digital Agency', 'CEO', 'activo'),
    ('550e8400-e29b-41d4-a716-446655440000', 'Carlos López', 'carlos.lopez@ejemplo.com', '+54 11 5555-5555', 'Startup Inc', 'Founder', 'activo');

-- Empresas de prueba
INSERT INTO empresas (user_id, nombre, industria, tamaño, sitio_web, descripcion)
VALUES
    ('550e8400-e29b-41d4-a716-446655440000', 'Tech Solutions', 'Tecnología', 'Mediana', 'https://techsolutions.example.com', 'Empresa de desarrollo de software'),
    ('550e8400-e29b-41d4-a716-446655440000', 'Digital Agency', 'Marketing Digital', 'Pequeña', 'https://digitalagency.example.com', 'Agencia de marketing digital'),
    ('550e8400-e29b-41d4-a716-446655440000', 'Startup Inc', 'Tecnología', 'Startup', 'https://startup.example.com', 'Startup de innovación tecnológica');

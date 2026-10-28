-- =====================================================================
--  SIRCI · Sistema Integral de Reportes Ciudadanos
--  Base de datos para MySQL 8.0 o superior (versión del modelo: 1.1)
--
--  Uso:   mysql -u root -p < sirci_mysql.sql
--  Crea la base "sirci", sus 9 tablas, 2 vistas y los datos iniciales.
-- =====================================================================

DROP DATABASE IF EXISTS sirci;
CREATE DATABASE sirci
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE sirci;

-- ---------------------------------------------------------------------
-- Catálogos fijos y de configuración
-- ---------------------------------------------------------------------

-- Áreas municipales (conjunto fijo, RN-04)
CREATE TABLE areas (
    id      TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre  VARCHAR(50)      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_areas_nombre (nombre)
) ENGINE=InnoDB;

-- Estados del ciclo de vida del reporte (RN-02)
CREATE TABLE estados (
    id      TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre  VARCHAR(30)      NOT NULL,
    orden   TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_estados_nombre (nombre),
    UNIQUE KEY uq_estados_orden (orden)
) ENGINE=InnoDB;

-- Categorías de reporte, con su área por defecto (RN-05, RF-21)
CREATE TABLE categorias (
    id               SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre           VARCHAR(50)       NOT NULL,
    area_id_defecto  TINYINT UNSIGNED  NOT NULL,
    activa           TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nombre (nombre),
    CONSTRAINT fk_categorias_area
        FOREIGN KEY (area_id_defecto) REFERENCES areas (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Usuarios
-- ---------------------------------------------------------------------

-- Ciudadanos, operadores y supervisores (RF-01, RF-12, RF-22)
CREATE TABLE usuarios (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    telefono        VARCHAR(30)  NULL,
    password_hash   VARCHAR(255) NOT NULL,
    rol             ENUM('ciudadano', 'operador', 'supervisor') NOT NULL DEFAULT 'ciudadano',
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    fecha_registro  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_rol (rol)
) ENGINE=InnoDB;

-- Tokens para restablecer la contraseña (RF-03). Se guarda solo el hash.
CREATE TABLE tokens_recuperacion (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED NOT NULL,
    token_hash  CHAR(64)     NOT NULL,
    expira_en   DATETIME     NOT NULL,
    usado       TINYINT(1)   NOT NULL DEFAULT 0,
    creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tokens_hash (token_hash),
    KEY idx_tokens_usuario (usuario_id),
    CONSTRAINT fk_tokens_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Reportes
-- ---------------------------------------------------------------------

-- Incidencia informada por un ciudadano (RF-04, RF-05, RF-24)
CREATE TABLE reportes (
    id              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED      NOT NULL,
    categoria_id    SMALLINT UNSIGNED NOT NULL,
    area_id         TINYINT UNSIGNED  NOT NULL,
    estado_id       TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    descripcion     VARCHAR(500)      NULL,
    latitud         DECIMAL(9,6)      NOT NULL,
    longitud        DECIMAL(9,6)      NOT NULL,
    barrio          VARCHAR(80)       NULL,
    fecha_creacion  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME         NULL,
    PRIMARY KEY (id),
    KEY idx_reportes_usuario (usuario_id),
    KEY idx_reportes_estado (estado_id),
    KEY idx_reportes_area (area_id),
    KEY idx_reportes_categoria (categoria_id),
    KEY idx_reportes_barrio (barrio),
    KEY idx_reportes_fecha (fecha_creacion),
    KEY idx_reportes_ubicacion (latitud, longitud),
    CONSTRAINT fk_reportes_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reportes_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reportes_area
        FOREIGN KEY (area_id) REFERENCES areas (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reportes_estado
        FOREIGN KEY (estado_id) REFERENCES estados (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT ck_reportes_latitud  CHECK (latitud  BETWEEN -90  AND 90),
    CONSTRAINT ck_reportes_longitud CHECK (longitud BETWEEN -180 AND 180)
) ENGINE=InnoDB;

-- Fotos de un reporte, hasta 4 (RF-06, RN-07). El límite lo controla la API.
CREATE TABLE fotos (
    id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    reporte_id  INT UNSIGNED     NOT NULL,
    url         VARCHAR(500)     NOT NULL,
    orden       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fotos_reporte_orden (reporte_id, orden),
    CONSTRAINT fk_fotos_reporte
        FOREIGN KEY (reporte_id) REFERENCES reportes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT ck_fotos_orden CHECK (orden BETWEEN 1 AND 4)
) ENGINE=InnoDB;

-- Historial de cambios de estado y de área (RF-15, RF-17, RN-03)
CREATE TABLE historial_estados (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    reporte_id    INT UNSIGNED     NOT NULL,
    estado_id     TINYINT UNSIGNED NOT NULL,
    area_id       TINYINT UNSIGNED NULL,
    usuario_id    INT UNSIGNED     NOT NULL,
    observacion   VARCHAR(500)     NULL,
    fecha_cambio  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_historial_reporte (reporte_id, fecha_cambio),
    KEY idx_historial_usuario (usuario_id),
    CONSTRAINT fk_historial_reporte
        FOREIGN KEY (reporte_id) REFERENCES reportes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_historial_estado
        FOREIGN KEY (estado_id) REFERENCES estados (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historial_area
        FOREIGN KEY (area_id) REFERENCES areas (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_historial_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Notificaciones dentro de la aplicación (RF-10)
CREATE TABLE notificaciones (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED NOT NULL,
    reporte_id  INT UNSIGNED NULL,
    mensaje     VARCHAR(255) NOT NULL,
    leida       TINYINT(1)   NOT NULL DEFAULT 0,
    fecha       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_usuario (usuario_id, leida, fecha),
    CONSTRAINT fk_notif_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notif_reporte
        FOREIGN KEY (reporte_id) REFERENCES reportes (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Vistas
-- ---------------------------------------------------------------------

-- Vista pública para el mapa: no expone datos del ciudadano (RF-08, RN-08)
CREATE VIEW v_reportes_publicos AS
SELECT r.id,
       c.nombre AS categoria,
       e.nombre AS estado,
       r.latitud,
       r.longitud,
       r.barrio,
       r.fecha_creacion
FROM reportes r
JOIN categorias c ON c.id = r.categoria_id
JOIN estados    e ON e.id = r.estado_id
WHERE e.nombre <> 'resuelto';

-- Vista para el tablero municipal (RF-18)
CREATE VIEW v_indicadores_categoria AS
SELECT c.nombre AS categoria,
       COUNT(*) AS total,
       SUM(e.nombre = 'en proceso') AS en_proceso,
       SUM(e.nombre = 'resuelto')   AS resueltos,
       ROUND(AVG(TIMESTAMPDIFF(HOUR, r.fecha_creacion, r.fecha_resolucion)), 1) AS horas_promedio_resolucion
FROM reportes r
JOIN categorias c ON c.id = r.categoria_id
JOIN estados    e ON e.id = r.estado_id
GROUP BY c.nombre;

-- ---------------------------------------------------------------------
-- Datos iniciales
-- ---------------------------------------------------------------------

INSERT INTO areas (nombre) VALUES
    ('Obras Públicas'),
    ('Alumbrado'),
    ('Higiene Urbana');

INSERT INTO estados (nombre, orden) VALUES
    ('recibido',   1),
    ('en proceso', 2),
    ('resuelto',   3);

INSERT INTO categorias (nombre, area_id_defecto) VALUES
    ('Bache',        (SELECT id FROM areas WHERE nombre = 'Obras Públicas')),
    ('Señalización', (SELECT id FROM areas WHERE nombre = 'Obras Públicas')),
    ('Luminaria',    (SELECT id FROM areas WHERE nombre = 'Alumbrado')),
    ('Basural',      (SELECT id FROM areas WHERE nombre = 'Higiene Urbana')),
    ('Otro',         (SELECT id FROM areas WHERE nombre = 'Obras Públicas'));

-- ---------------------------------------------------------------------
-- Consultas de referencia (no se ejecutan; copiar y adaptar)
-- ---------------------------------------------------------------------

-- Posibles duplicados de un reporte nuevo (RF-07, RN-06): misma categoría,
-- activos y a 50 m o menos. Primero se filtra por una caja aproximada
-- (aprox. 0,00045 grados = 50 m) para usar el índice (latitud, longitud).
--
-- SET @lat = -26.184900, @lon = -58.173100, @cat = 1;
-- SELECT r.id, r.descripcion,
--        ST_Distance_Sphere(POINT(r.longitud, r.latitud), POINT(@lon, @lat)) AS metros
-- FROM reportes r
-- JOIN estados e ON e.id = r.estado_id
-- WHERE r.categoria_id = @cat
--   AND e.nombre <> 'resuelto'
--   AND r.latitud  BETWEEN @lat - 0.0005 AND @lat + 0.0005
--   AND r.longitud BETWEEN @lon - 0.0005 AND @lon + 0.0005
--   AND ST_Distance_Sphere(POINT(r.longitud, r.latitud), POINT(@lon, @lat)) <= 50
-- ORDER BY metros;

-- Reportes por barrio y categoría (tablero):
--
-- SELECT r.barrio, c.nombre AS categoria, COUNT(*) AS cantidad
-- FROM reportes r JOIN categorias c ON c.id = r.categoria_id
-- GROUP BY r.barrio, c.nombre ORDER BY cantidad DESC;

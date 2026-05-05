-- =============================================================================
-- Reference data seed — run after all table files (001–014)
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Roles (ordered by ascending privilege level)
-- guest is the base role for unauthenticated requests; all others inherit from it.
-- -----------------------------------------------------------------------------
INSERT INTO role (role_id) VALUES
    ('guest'),
    ('Sales'),
    ('Warehouse'),
    ('Warehouse Supervisor'),
    ('Credit Manager'),
    ('DC Warehouse'),
    ('Manager'),
    ('Administrator')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- -----------------------------------------------------------------------------
-- Sample stores
-- Replace pqa_email values with real addresses before deploying.
-- -----------------------------------------------------------------------------
INSERT INTO store (store_number, city, state, pqa_email) VALUES
    (207, 'Leeds',      'AL', 'pqa-207@example.com'),
    (112, 'Birmingham', 'AL', 'pqa-112@example.com')
ON DUPLICATE KEY UPDATE
    city      = VALUES(city),
    state     = VALUES(state),
    pqa_email = VALUES(pqa_email);

-- -----------------------------------------------------------------------------
-- ACL: Resources
-- -----------------------------------------------------------------------------
INSERT INTO acl_resource (resource_id, label) VALUES
    ('public',  'Public'),
    ('user',    'User')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- -----------------------------------------------------------------------------
-- ACL: Privileges
-- public resource
-- -----------------------------------------------------------------------------
INSERT INTO acl_privilege (resource_pk, privilege_id, label)
SELECT r.resource_pk, p.privilege_id, p.label
FROM acl_resource r
JOIN (
    SELECT 'read' AS privilege_id, 'Read' AS label
) p ON r.resource_id = 'public'
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- user resource
INSERT INTO acl_privilege (resource_pk, privilege_id, label)
SELECT r.resource_pk, p.privilege_id, p.label
FROM acl_resource r
JOIN (
    SELECT 'read'     AS privilege_id, 'Read'     AS label UNION ALL
    SELECT 'create',                   'Create'            UNION ALL
    SELECT 'update',                   'Update'            UNION ALL
    SELECT 'delete',                   'Delete'            UNION ALL
    SELECT 'login',                    'Login'             UNION ALL
    SELECT 'register',                 'Register'
) p ON r.resource_id = 'user'
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- -----------------------------------------------------------------------------
-- ACL: Role inheritance
-- guest is the root; all authenticated roles inherit from guest.
-- Higher roles inherit from their direct parent.
-- -----------------------------------------------------------------------------
INSERT INTO acl_role_parent (role_pk, parent_pk)
SELECT child.id, parent.id
FROM role child
JOIN role parent ON (
    (child.role_id = 'Sales'               AND parent.role_id = 'guest')             OR
    (child.role_id = 'Warehouse'           AND parent.role_id = 'guest')             OR
    (child.role_id = 'Warehouse Supervisor' AND parent.role_id = 'Warehouse')        OR
    (child.role_id = 'Credit Manager'      AND parent.role_id = 'Warehouse Supervisor') OR
    (child.role_id = 'DC Warehouse'        AND parent.role_id = 'Warehouse Supervisor') OR
    (child.role_id = 'Manager'             AND parent.role_id = 'DC Warehouse')      OR
    (child.role_id = 'Administrator'       AND parent.role_id = 'Manager')
)
ON DUPLICATE KEY UPDATE parent_pk = VALUES(parent_pk);

-- -----------------------------------------------------------------------------
-- ACL: Baseline rules
-- guest is allowed: public/read, user/login, user/register
-- All authenticated roles (inherit guest) are denied: user/login, user/register
-- -----------------------------------------------------------------------------

-- guest allow: public → read
INSERT INTO acl_rule (role_pk, resource_pk, privilege_pk, type)
SELECT ro.id, pr.privilege_pk, pr.privilege_pk, 'allow'
FROM role ro
JOIN acl_privilege pr ON pr.privilege_id = 'read'
JOIN acl_resource  re ON re.resource_pk  = pr.resource_pk AND re.resource_id = 'public'
WHERE ro.role_id = 'guest'
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- guest allow: user → login
INSERT INTO acl_rule (role_pk, resource_pk, privilege_pk, type)
SELECT ro.id, pr.resource_pk, pr.privilege_pk, 'allow'
FROM role ro
JOIN acl_privilege pr ON pr.privilege_id = 'login'
JOIN acl_resource  re ON re.resource_pk  = pr.resource_pk AND re.resource_id = 'user'
WHERE ro.role_id = 'guest'
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- guest allow: user → register
INSERT INTO acl_rule (role_pk, resource_pk, privilege_pk, type)
SELECT ro.id, pr.resource_pk, pr.privilege_pk, 'allow'
FROM role ro
JOIN acl_privilege pr ON pr.privilege_id = 'register'
JOIN acl_resource  re ON re.resource_pk  = pr.resource_pk AND re.resource_id = 'user'
WHERE ro.role_id = 'guest'
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- All authenticated roles deny: user → login
INSERT INTO acl_rule (role_pk, resource_pk, privilege_pk, type)
SELECT ro.id, pr.resource_pk, pr.privilege_pk, 'deny'
FROM role ro
JOIN acl_privilege pr ON pr.privilege_id = 'login'
JOIN acl_resource  re ON re.resource_pk  = pr.resource_pk AND re.resource_id = 'user'
WHERE ro.role_id IN ('Sales','Warehouse','Warehouse Supervisor','Credit Manager','DC Warehouse','Manager','Administrator')
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- All authenticated roles deny: user → register
INSERT INTO acl_rule (role_pk, resource_pk, privilege_pk, type)
SELECT ro.id, pr.resource_pk, pr.privilege_pk, 'deny'
FROM role ro
JOIN acl_privilege pr ON pr.privilege_id = 'register'
JOIN acl_resource  re ON re.resource_pk  = pr.resource_pk AND re.resource_id = 'user'
WHERE ro.role_id IN ('Sales','Warehouse','Warehouse Supervisor','Credit Manager','DC Warehouse','Manager','Administrator')
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- -----------------------------------------------------------------------------
-- ACL: Additional resources — dashboard, admin.user
-- -----------------------------------------------------------------------------
INSERT INTO acl_resource (resource_id, label) VALUES
    ('dashboard', 'Dashboard'),
    ('admin.user', 'Admin — User Management')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- -----------------------------------------------------------------------------
-- ACL: Privileges for dashboard
-- -----------------------------------------------------------------------------
INSERT INTO acl_privilege (resource_pk, privilege_id, label)
SELECT r.resource_pk, 'read', 'Read'
FROM acl_resource r WHERE r.resource_id = 'dashboard'
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- -----------------------------------------------------------------------------
-- ACL: Privileges for user resource — logout
-- -----------------------------------------------------------------------------
INSERT INTO acl_privilege (resource_pk, privilege_id, label)
SELECT r.resource_pk, 'logout', 'Logout'
FROM acl_resource r WHERE r.resource_id = 'user'
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- -----------------------------------------------------------------------------
-- ACL: Privileges for admin.user
-- -----------------------------------------------------------------------------
INSERT INTO acl_privilege (resource_pk, privilege_id, label)
SELECT r.resource_pk, p.privilege_id, p.label
FROM acl_resource r
JOIN (
    SELECT 'read'   AS privilege_id, 'Read'   AS label UNION ALL
    SELECT 'create',                 'Create'          UNION ALL
    SELECT 'update',                 'Update'          UNION ALL
    SELECT 'delete',                 'Delete'
) p ON r.resource_id = 'admin.user'
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- -----------------------------------------------------------------------------
-- ACL: Rules — dashboard (authenticated roles only)
-- Sales is the lowest authenticated role; it inherits down to Administrator
-- via the role hierarchy, so granting Sales is sufficient.
-- -----------------------------------------------------------------------------
INSERT INTO acl_rule (role_pk, resource_pk, privilege_pk, type)
SELECT ro.id, pr.resource_pk, pr.privilege_pk, 'allow'
FROM role ro
JOIN acl_privilege pr ON pr.privilege_id = 'read'
JOIN acl_resource  re ON re.resource_pk  = pr.resource_pk AND re.resource_id = 'dashboard'
WHERE ro.role_id IN ('Sales','Warehouse')
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- -----------------------------------------------------------------------------
-- ACL: Rules — user/logout
-- guest denied; all authenticated roles allowed.
-- -----------------------------------------------------------------------------
INSERT INTO acl_rule (role_pk, resource_pk, privilege_pk, type)
SELECT ro.id, pr.resource_pk, pr.privilege_pk, 'allow'
FROM role ro
JOIN acl_privilege pr ON pr.privilege_id = 'logout'
JOIN acl_resource  re ON re.resource_pk  = pr.resource_pk AND re.resource_id = 'user'
WHERE ro.role_id IN ('Sales','Warehouse')
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- -----------------------------------------------------------------------------
-- ACL: Rules — admin.user (Manager and Administrator)
-- Manager inherits to Administrator via hierarchy; grant Manager only.
-- -----------------------------------------------------------------------------
INSERT INTO acl_rule (role_pk, resource_pk, privilege_pk, type)
SELECT ro.id, pr.resource_pk, pr.privilege_pk, 'allow'
FROM role ro
JOIN acl_privilege pr ON pr.privilege_id IN ('read','create','update','delete')
JOIN acl_resource  re ON re.resource_pk  = pr.resource_pk AND re.resource_id = 'admin.user'
WHERE ro.role_id = 'Manager'
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- -----------------------------------------------------------------------------
-- ACL: Route → resource + privilege mappings
-- -----------------------------------------------------------------------------
INSERT INTO acl_route_privilege (route_name, resource_pk, privilege_pk)
SELECT routes.route_name, pr.resource_pk, pr.privilege_pk
FROM (
    -- Public / login / register pages
    SELECT 'user.login'              AS route_name, 'user'       AS resource_id, 'login'    AS privilege_id UNION ALL
    SELECT 'user.login.post',                       'user',                      'login'                    UNION ALL
    SELECT 'user.register',                         'user',                      'register'                 UNION ALL
    SELECT 'user.register.post',                    'user',                      'register'                 UNION ALL
    -- Email verification — public
    SELECT 'user.verify-email',                     'public',                    'read'                     UNION ALL
    SELECT 'user.verify-email.resend',              'public',                    'read'                     UNION ALL
    -- Authenticated user routes
    SELECT 'user.logout',                           'user',                      'logout'                   UNION ALL
    -- Dashboard
    SELECT 'dashboard',                             'dashboard',                 'read'                     UNION ALL
    SELECT 'api.ping',                              'public',                    'read'                     UNION ALL
    -- Admin user management
    SELECT 'admin.user.list',                       'admin.user',                'read'                     UNION ALL
    SELECT 'admin.create.user',                     'admin.user',                'create'                   UNION ALL
    SELECT 'admin.update.user',                     'admin.user',                'update'                   UNION ALL
    SELECT 'admin.toggle.user',                     'admin.user',                'update'
) AS routes
JOIN acl_resource  re ON re.resource_id  = routes.resource_id
JOIN acl_privilege pr ON pr.privilege_id = routes.privilege_id AND pr.resource_pk = re.resource_pk
ON DUPLICATE KEY UPDATE resource_pk = VALUES(resource_pk), privilege_pk = VALUES(privilege_pk);

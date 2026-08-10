<?php
// =============================================================================
// UNIFIND - SISTEMA DE ACHADOS E PERDIDOS (MONGODB + REDIS)
// Configuração de Conexão com MongoDB e Redis
// =============================================================================

class Database {
    private static $mongoManager = null;
    private static $redis = null;
    private static $dbName = "unifind_db";

    public static function getMongoManager() {
        if (self::$mongoManager === null) {
            $mongoUri = getenv('MONGO_URI') ?: 'mongodb://localhost:27017';
            try {
                self::$mongoManager = new MongoDB\Driver\Manager($mongoUri);
            } catch (Exception $e) {
                die("Erro de conexão com MongoDB: " . $e->getMessage());
            }
        }
        return self::$mongoManager;
    }

    public static function getDbName() {
        return self::$dbName;
    }

    public static function getRedis() {
        if (self::$redis === null) {
            $redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
            $redisPort = getenv('REDIS_PORT') ?: 6379;
            try {
                self::$redis = new Redis();
                self::$redis->connect($redisHost, (int)$redisPort, 2.5);
            } catch (Exception $e) {
                // Instância de fallback em mock ou silêncio para ambiente de teste sem servidor ativo
                self::$redis = null;
            }
        }
        return self::$redis;
    }
}

// Helpers para consultas Mongo com MongoDB\Driver
function getMongoCollection($collectionName, $filter = [], $options = []) {
    $manager = Database::getMongoManager();
    $query = new MongoDB\Driver\Query($filter, $options);
    $namespace = Database::getDbName() . "." . $collectionName;
    return $manager->executeQuery($namespace, $query)->toArray();
}

function getMongoDocumentById($collectionName, $id) {
    try {
        $objectId = new MongoDB\BSON\ObjectId($id);
        $docs = getMongoCollection($collectionName, ['_id' => $objectId]);
        return count($docs) > 0 ? $docs[0] : null;
    } catch (Exception $e) {
        return null;
    }
}

function insertMongoDocument($collectionName, $document) {
    $manager = Database::getMongoManager();
    $bulk = new MongoDB\Driver\BulkWrite();
    if (!isset($document['_id'])) {
        $document['_id'] = new MongoDB\BSON\ObjectId();
    }
    $bulk->insert($document);
    $namespace = Database::getDbName() . "." . $collectionName;
    $result = $manager->executeBulkWrite($namespace, $bulk);
    return $document['_id'];
}

function updateMongoDocument($collectionName, $filter, $update) {
    $manager = Database::getMongoManager();
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update($filter, $update);
    $namespace = Database::getDbName() . "." . $collectionName;
    return $manager->executeBulkWrite($namespace, $bulk);
}

<?php

class MyGridFS extends MongoDB\GridFS\Bucket
{
    //旧驱动有findOne方法，metadata均放在返回对象的file属性以数组方式存放
    function findOne($filename)
    {
        $ret = new stdClass();
        $file = array();
        $o=$this->getCollectionWrapper()->findFileByFilenameAndRevision($filename,0);

        if ($o==NULL) return NULL;

        $file['md5'] = $o->md5;
        $file['length'] = $o->length;
        $file['filename'] = $o->filename;
        $file['chunkSize'] = $o->chunkSize;
        $file['_id'] = $o->_id;
        $file['uploadDate'] = $o->uploadDate;

        $ret->file = $file;
        return $ret;   
    }

    //旧的驱动可以将文件字节字符串直接存入gridfs，新的不再支持，所以变通先存入一个临时文件，然后再打开临时文件的stream进行写入
    //为了提高性能，临时文件所在目录最好为基于内存
    function storeBytes($bytes,$metadata)
    {
        $filename = $metadata['filename'];       
        if (!$filename) return false;

        $tmpfile = WORK_TEMP_PATH.md5($filename.time());
        file_put_contents($tmpfile,$bytes);

        $handle = fopen($tmpfile, "r");
        $this->uploadFromStream($filename,$handle);
        @unlink($tmpfile);
    }

    function storeFile($filepath,$metadata)
    {
        $filename = $metadata['filename'];       
        if (!$filename) return false;

        $handle = fopen($filepath, "r");
        $this->uploadFromStream($filename,$handle);
    }   

    function getBytes($filename,array $options = [])
    {
        $resource = $this->openDownloadStreamByName($filename,$options);
        return stream_get_contents($resource);       
    }
}

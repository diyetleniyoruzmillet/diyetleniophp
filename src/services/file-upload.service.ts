import multer, { FileFilterCallback } from 'multer';
import path from 'path';
import fs from 'fs';
import { Request } from 'express';
import { config } from '../config';
import { ValidationError } from '../utils/errors';
import crypto from 'crypto';

export class FileUploadService {
  private uploadPath: string;

  constructor() {
    this.uploadPath = config.upload.path;
    this.ensureUploadDirectories();
  }

  private ensureUploadDirectories() {
    const directories = [
      'profiles',
      'articles',
      'recipes',
      'documents',
      'temp',
      'diet-plans',
    ];

    directories.forEach((dir) => {
      const dirPath = path.join(this.uploadPath, dir);
      if (!fs.existsSync(dirPath)) {
        fs.mkdirSync(dirPath, { recursive: true });
      }
    });
  }

  getMulterConfig(destination: string = 'temp', allowedTypes: string[] = []) {
    const storage = multer.diskStorage({
      destination: (req, file, cb) => {
        const destPath = path.join(this.uploadPath, destination);
        cb(null, destPath);
      },
      filename: (req, file, cb) => {
        const uniqueSuffix = crypto.randomBytes(16).toString('hex');
        const ext = path.extname(file.originalname);
        const filename = `${uniqueSuffix}${ext}`;
        cb(null, filename);
      },
    });

    const fileFilter = (
      req: Request,
      file: Express.Multer.File,
      cb: FileFilterCallback
    ) => {
      if (allowedTypes.length === 0) {
        return cb(null, true);
      }

      const ext = path.extname(file.originalname).toLowerCase().substring(1);
      if (allowedTypes.includes(ext)) {
        cb(null, true);
      } else {
        cb(new ValidationError(`File type .${ext} is not allowed`));
      }
    };

    return multer({
      storage,
      fileFilter,
      limits: {
        fileSize: config.upload.maxSize,
      },
    });
  }

  uploadImage() {
    return this.getMulterConfig('temp', config.upload.allowedTypes.images);
  }

  uploadDocument() {
    return this.getMulterConfig('documents', config.upload.allowedTypes.documents);
  }

  uploadProfilePhoto() {
    return this.getMulterConfig('profiles', config.upload.allowedTypes.images);
  }

  async deleteFile(filePath: string): Promise<void> {
    try {
      if (fs.existsSync(filePath)) {
        fs.unlinkSync(filePath);
      }
    } catch (error) {
      console.error('Error deleting file:', error);
      throw error;
    }
  }

  async moveFile(sourcePath: string, destinationFolder: string): Promise<string> {
    const filename = path.basename(sourcePath);
    const destPath = path.join(this.uploadPath, destinationFolder, filename);

    // Ensure destination directory exists
    const destDir = path.dirname(destPath);
    if (!fs.existsSync(destDir)) {
      fs.mkdirSync(destDir, { recursive: true });
    }

    // Move file
    fs.renameSync(sourcePath, destPath);

    return path.relative(this.uploadPath, destPath);
  }

  getFileUrl(relativePath: string): string {
    return `${config.app.url}/uploads/${relativePath}`;
  }
}

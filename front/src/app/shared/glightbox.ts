export interface GLightboxInstance {
  destroy(): void;
}

export interface GLightboxOptions {
  selector?: string;
  [key: string]: unknown;
}

export default function GLightbox(_options: GLightboxOptions = {}): GLightboxInstance {
  return {
    destroy() {
      // no-op fallback implementation
    },
  };
}

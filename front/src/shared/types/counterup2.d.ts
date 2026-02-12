declare module 'counterup2' {
  export interface CounterUpOptions {
    duration?: number; // ms
    delay?: number; // ms
    action?: 'start' | 'stop';
  }

  const counterUp: (el: HTMLElement, options?: CounterUpOptions) => void;
  export default counterUp;
}

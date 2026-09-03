from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
import os


def main() -> None:
    os.chdir(r"D:\datasets\mi-prawo-jazdy-2026\app-media")
    ThreadingHTTPServer(("127.0.0.1", 8081), SimpleHTTPRequestHandler).serve_forever()


if __name__ == "__main__":
    main()

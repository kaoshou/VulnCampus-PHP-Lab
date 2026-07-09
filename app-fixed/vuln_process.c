#include <stdio.h>
#include <string.h>
#include <stdlib.h>

int main(int argc, char *argv[]) {
    if (argc < 2) {
        printf("Usage: %s <input>\n", argv[0]);
        return 1;
    }
    
    char buffer[64];
    // 安全修補：使用 strncpy 並確保結尾有 NULL 終止字元
    strncpy(buffer, argv[1], sizeof(buffer) - 1);
    buffer[sizeof(buffer) - 1] = '\0';
    
    printf("Successfully processed input in C: %s\n", buffer);
    return 0;
}

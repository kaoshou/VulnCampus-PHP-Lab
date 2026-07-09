#include <stdio.h>
#include <string.h>
#include <stdlib.h>

int main(int argc, char *argv[]) {
    if (argc < 2) {
        printf("Usage: %s <input>\n", argv[0]);
        return 1;
    }
    
    char buffer[64];
    // 經典緩衝區溢位漏洞 (strcpy 未檢查邊界)
    strcpy(buffer, argv[1]);
    
    printf("Successfully processed input in C: %s\n", buffer);
    return 0;
}

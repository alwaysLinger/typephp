#include <typephp_runtime.h>

TYPEPHP_RUNTIME_INIT_FUNCTION(consumer);
TYPEPHP_RUNTIME_SHUTDOWN_FUNCTION(consumer);

int main(int argc, char **argv) {
    for (int request = 0; request < 3; ++request) {
        int status = TYPEPHP_RUNTIME_INIT(consumer)(argc, argv);
        TYPEPHP_RUNTIME_SHUTDOWN(consumer)();
        if (status != 0) {
            return status;
        }
    }
    return 0;
}

#include <cstdio>

extern "C" int typephp_failing_library_request_startup() {
    std::puts("startup-failed");
    return -1;
}

extern "C" void typephp_failing_library_request_shutdown() {
    std::puts("unexpected-shutdown");
}

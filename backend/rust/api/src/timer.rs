use std::time::Instant;

pub struct Timer {
    start: Instant,
}

impl Timer {
    pub fn new() -> Self {
        Self {
            start: Instant::now(),
        }
    }

    pub fn elapsed(&self) -> f64 {
        let secs = self.start.elapsed().as_secs_f64();
        (secs * 10000.0).round() / 10000.0
    }
}

impl Default for Timer {
    fn default() -> Self {
        Self::new()
    }
}

use aws_lambda_events::event::s3::S3Event;
use aws_sdk_s3::Client;
use image::io::Reader as ImageReader;
use lambda_runtime::{run, service_fn, Error, LambdaEvent};
use std::io::Cursor;

async fn function_handler(event: LambdaEvent<S3Event>) -> Result<(), Error> {
    let shared_config = aws_config::load_from_env().await;
    let client = Client::new(&shared_config);

    for record in event.payload.records {
        let bucket = record.s3.bucket.name.unwrap();
        let key = record.s3.object.key.unwrap();

        if !key.starts_with("uploads/") || key.contains("/thumbnails/") {
            continue;
        }

        let data = client
            .get_object()
            .bucket(&bucket)
            .key(&key)
            .send()
            .await?;

        let bytes = data.body.collect().await?.into_bytes();

        let img = ImageReader::new(Cursor::new(bytes))
            .with_guessed_format()?
            .decode()?;

        let thumbnail = img.thumbnail(300, 300);
        let mut buffer = Vec::new();
        thumbnail.write_to(&mut Cursor::new(&mut buffer), image::ImageOutputFormat::Jpeg(80))?;

        let thumb_key = key.replace("uploads/", "uploads/thumbnails/");
        client
            .put_object()
            .bucket(&bucket)
            .key(&thumb_key)
            .body(buffer.into())
            .content_type("image/jpeg")
            .send()
            .await?;
        
        println!("Generated thumbnail: {}", thumb_key);
    }

    Ok(())
}

#[tokio::main]
async fn main() -> Result<(), Error> {
    tracing_subscriber::fmt()
        .with_max_level(tracing::Level::INFO)
        .with_target(false)
        .init();

    run(service_fn(function_handler)).await
}

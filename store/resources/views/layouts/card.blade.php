<div class="col-sm-6 col-md-4">
    <div class="thumbnail">
        <div class="labels">
            @if($product->isNew())
                <span class="badge badge-success">Новинка</span>
            @endif
            @if($product->isRecommend())
                    <span class="badge badge-warning">Рекомендуем</span>
            @endif
            @if($product->isHit())
                    <span class="badge badge-danger">Хит продаж!</span>
            @endif
        </div>
        <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image) }}" alt="">
        <div class="caption">
            <h3>{{ $product->name }}</h3>
            <p>{{ $product->price }} руб.</p>
            <p>
                <form action="{{ route('basket-add', $product) }}" method="post">
                    @csrf
                @if($product->isAvailable())
                    <button type="submit" class="btn btn-primary" role="button">В корзину</button>
                @else
                    Не доступен
                @endif
                    <a href="{{ route('product', [$product->category, $product]) }}" class="btn btn-default"
                       role="button">Подробнее</a>
                </form>
            </p>
        </div>
    </div>
</div>

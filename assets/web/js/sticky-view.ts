export function enableStickyView(
  headerSelector: string,
  previewSelector: string
): void {
  const header = document.querySelector<HTMLElement>(headerSelector);
  const preview = document.querySelector<HTMLElement>(previewSelector);

  if (!header || !preview) return;

  const updateStickyTop = () => {
    const headerHeight = header.offsetHeight;
    preview.style.position = "sticky";
    preview.style.top = `${headerHeight}px`;
  };

  updateStickyTop();

  const resizeObserver = new ResizeObserver(updateStickyTop);
  resizeObserver.observe(header);

  window.addEventListener("resize", updateStickyTop);

  window.addEventListener("zoom", updateStickyTop); 
}
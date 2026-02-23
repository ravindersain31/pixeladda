import React, {useContext, useEffect, useMemo, useState} from "react";
import {
    ArtworkBrowser,
    ArtworkWrapper,
    Artwork,
    ArtworkSelector,
    ArtworkSearchWrapper,
    ArtworkSearch,
    ArtworkCategory,
    Loading,
    NoteMessage,
    EmptyNote
} from "./styled";
import CanvasContext from "@react/editor/context/canvas.ts";
import fabric from "@react/editor/canvas/fabric.ts";
import axios from "axios";
import {useAppSelector} from "@react/editor/hook.ts";
import {Empty, Input} from "antd";
import {isMobile, isIOS} from "react-device-detect";
import _ from "lodash";

const {TextArea} = Input;
import AdditionalNote from "@react/editor/components/AdditionalNote";
import useArtworkUpload from "@react/editor/plugin/useArtworkUpload";
import { getStoreInfo } from "@react/editor/helper/editor";
const storeEmail = getStoreInfo().storeEmail;

const BrowseArtwork = () => {
    const config = useAppSelector((state) => state.config);
    const [isLoading, setLoading] = useState<boolean>(false);
    const [category, setCategory] = useState<number>(1);
    const [search, setSearch] = useState<string>("");
    const [artworks, setArtworks] = useState<any>([]);

    const canvasContext = useContext(CanvasContext);

    useEffect(() => {
        (async () => {
            await loadArtworks(category);
        })();
    }, []);

    const onCategoryChange = async (option: any) => {
        setCategory(option.value);
        setSearch("");
        await loadArtworks(option.value, null);
    }

    const onSearchChange = async (value: string) => {
        setSearch(value);
        await loadArtworks(category, value);
    }

    const debouncedSearch = useMemo(() => {
      return _.debounce((value: string) => {
        onSearchChange(value);
      }, 300);
    }, [category]);

    useEffect(() => {
      return () => {
        debouncedSearch.cancel();
      };
    }, [debouncedSearch]);

    const loadArtworks = async (categoryId: number, query: string | null = null) => {
        setLoading(true);
        let url = `${config.links.list_artwork}/${categoryId}`;
        if (query) {
            url += `?q=${query}`;
        }
        const {data, status} = await axios.get(url);
        if (status === 200) {
            setArtworks(data);
        }
        setLoading(false);
    }

    const onArtworkChoose = (artwork: any) => {
        const ext = artwork.image.split('.').pop();
        let baseUrl = 'https://static.yardsignplus.com/fit-in/200x200/clipart';
        if (['gif'].includes(ext)) {
            baseUrl = 'https://static.yardsignplus.com/clipart';
        }
        fabric.util.loadImage(`${baseUrl}/${artwork.image}`, (img: any) => {
            const image = new fabric.Image(img);
            image.left = 20;
            image.top = 20;
            image.custom = {
                type: 'Artwork'
            }
            image.scaleToWidth(100);
            image.scaleToHeight(100);
            canvasContext.canvas.add(image);
            canvasContext.canvas.requestRenderAll();
            canvasContext.canvas.setActiveObject(image);
        });
        useArtworkUpload();
    }

    return <>
        <ArtworkSearchWrapper>
            <ArtworkCategory
                isDisabled={isLoading}
                placeholder="Category"
                onChange={(value: any) => onCategoryChange(value)}
                defaultValue={{ value: '1', label: 'Most Popular' }}
                isSearchable={false}
                options={config.artwork.categories.map((cat: any) => ({
                    label: cat.title,
                    value: cat.id,
                }))}
            />
            <ArtworkSearch
                enterButton
                allowClear
                size="large"
                placeholder="Search Artwork"
                value={search}
                onChange={(event: any) => {
                    setSearch(event.currentTarget.value);
                    debouncedSearch(event.currentTarget.value);
                }}
            />
        </ArtworkSearchWrapper>
        <ArtworkBrowser style={{
            overflow: isLoading ? 'hidden' : 'scroll',
            height: artworks.length === 0 ? '200px' : '250px',
            marginBottom: '0.5rem'
        }}>
            {isLoading ? (
                <Loading>Loading...</Loading>
            ) : (
                artworks.length === 0 ? (<>
                        <EmptyNote
                            image="https://gw.alipayobjects.com/zos/antfincdn/ZHrcdLPrvN/empty.svg"
                            description={
                                <span>
                                No results found, please try again. For questions please
                                call <a href="tel:+1-877-958-1499">+1-877-958-1499</a> ,
                                message us on our{" "}
                                    <a href="javascript:void(Tawk_API.toggle())">live chat</a>,
                                or email{" "}
                                    <a href={`mailto:${storeEmail}`}>
                                    {storeEmail}
                                </a>
                            </span>
                            }
                        /></>
                ) : (
                    artworks.map((artwork: any, index: number) => {
                        const ext = artwork.image.split('.').pop();
                        let imageUrl = `https://static.yardsignplus.com/fit-in/500x500/clipart/${artwork.image}`;
                        if (['gif'].includes(ext)) {
                            imageUrl = `https://static.yardsignplus.com/clipart/${artwork.image}`;
                        }
                        return <ArtworkWrapper
                            className={isIOS ? 'ios' : ''}
                            onClick={() => onArtworkChoose(artwork)}
                            key={`artwork_${category}_${artwork.image}_${index}`}>
                            <Artwork
                                src={imageUrl}
                                alt={artwork.tags}
                            />
                            <ArtworkSelector className="artwork-selector">
                                Click to Select
                            </ArtworkSelector>
                        </ArtworkWrapper>
                    })))}
            {!isLoading && <NoteMessage>DON'T SEE YOUR ARTWORK? PLEASE LEAVE US A COMMENT!</NoteMessage>}
        </ArtworkBrowser>
        <AdditionalNote showNeedAssistance={isMobile}/>
    </>;
}

export default BrowseArtwork;